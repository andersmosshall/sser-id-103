<?php

namespace Drupal\simple_school_reports_schema_support\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface;
use Drupal\simple_school_reports_schema_support\Service\CalendarEventsSyncServiceInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a StudentSchemaBlock for the current student tab.
 *
 * @Block(
 *  id = "student_schema_block",
 *  admin_label = @Translation("Student schema block"),
 * )
 */
class StudentSchemaBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    protected ModuleHandlerInterface $moduleHandler,
    protected RouteMatchInterface $routeMatch,
    protected RequestStack $requestStack,
    protected SchoolWeekServiceInterface $schoolWeekService,
    protected CalendarEventsSyncServiceInterface $calendarEventsSyncService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('simple_school_reports_entities.school_week_service'),
      $container->get('simple_school_reports_schema_support.calendar_events_sync'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route', 'current_day', 'url.query_args:from', 'url.query_args:to']);
    $cache->addCacheTags(['school_week_list', 'node_list:day_absence', 'node_list:course_attendance_report', 'school_week_deviation_list', 'ssr_school_week_per_grade', 'ssr_schema_entry_list', 'ssr_calendar_event_list']);
    $build = [];

    $user = $this->routeMatch->getParameter('user');
    if (!$user instanceof UserInterface) {
      $cache->applyTo($build);
      return $build;
    }
    $cache->addCacheableDependency($user);

    $uid = $user->id();
    $from = $this->requestStack->getCurrentRequest()->get('from');
    $to = $this->requestStack->getCurrentRequest()->get('to');
    if (!$uid || !$from || !$to) {
      $cache->applyTo($build);
      return $build;
    }

    // Support maximum of 30 days diff.
    $from = (int) $from;
    $to = (int) $to;

    $school_week = NULL;
    $from_time_object = (new \DateTime())->setTimestamp($from);
    $from_time_object->setTime(0, 0, 0);
    $to_time_object = (new \DateTime())->setTimestamp($to);
    $to_time_object->setTime(23, 59, 59);

    if ($from < $to && $to - $from < 30 * 24 * 60 * 60) {
      $school_week = $this->schoolWeekService->getSchoolWeek($uid, $from_time_object);
    }
    if (!$school_week) {
      $build['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No schema to show.'),
      ];
      $cache->applyTo($build);
      return $build;
    }

    $weeks = [];

    $day_walk = clone $from_time_object;
    while ($day_walk->getTimestamp() <= $to_time_object->getTimestamp()) {
      $week_id = $day_walk->format('Y-W');
      $date = clone $day_walk;
      $day_index = $day_walk->format('N');
      $weeks[$week_id][$day_index] = [
        'date' => $date,
        'lessons' => [],
      ];
      $day_walk->add(new \DateInterval('P1D'));
    }

    $calendar_events = $this->calendarEventsSyncService->calculateStudentCourseCalendarEvents($user->id(), $from_time_object->getTimestamp(), $to_time_object->getTimestamp());
    foreach ($calendar_events as $calendar_event) {
      $event_from = $calendar_event->get('from')->value;
      $event_from = (new \DateTime())->setTimestamp($event_from);

      $week_id = $event_from->format('Y-W');

      if (!isset($weeks[$week_id])) {
        continue;
      }
      $day_index = $event_from->format('N');
      if (!isset($weeks[$week_id][$day_index])) {
        continue;
      }

      $subject_code = '';
      $course = $calendar_event->get('field_course')->entity;
      if ($course) {
        $subject_code = SchoolSubjectHelper::getSubjectShortName($course->get('field_school_subject')->target_id);
      }

      $weeks[$week_id][$day_index]['lessons'][] = [
        'from' => $event_from->getTimestamp(),
        'to' => $calendar_event->get('to')->value,
        'subject' => $subject_code,
      ];

    }

    if (empty($weeks)) {
      $build['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No schema to show.'),
      ];
      $cache->applyTo($build);
      return $build;
    }

    // Make one table for each week.
    foreach ($weeks as $week_id => $day_data) {
      $day_map = [
        1 => t('Monday'),
        2 => t('Tuesday'),
        3 => t('Wednesday'),
        4 => t('Thursday'),
        5 => t('Friday'),
        6 => t('Saturday'),
        7 => t('Sunday'),
      ];

      $headers = [];
      $row = [];
      $include_weekend = FALSE;
      foreach ($day_data as $day_index => $data) {
        $lessons = $data['lessons'];
        if ($day_index > 5 && !empty($lessons)) {
          $include_weekend = TRUE;
        }
        $headers[$day_index] = $day_map[$day_index] . ' - ' . $data['date']->format('j/n');
        $row[$day_index]['data'] = [];

        foreach ($lessons as $lesson) {
          $time_from = date('H:i', $lesson['from']);
          $time_to = date('H:i', $lesson['to']);

          $suffix = '';
          if ($lesson['subject'] !== 'n/a') {
            $name = $lesson['subject'];

            if ($name === 'CBT') {
              $name = 'BT';
            }

            if (str_contains($name, ':')) {
              $name = explode(':', $name);
              $name = array_pop($name);
            }
            $suffix .= ' ' . $name;
          }

          $row[$day_index]['data'][] = [
            '#type' => 'container',
            'value' => [
              '#markup' => $time_from . ' - ' . $time_to . $suffix,
            ],
          ];
        }
      }

      if (!$include_weekend) {
        unset($headers[6]);
        unset($headers[7]);
        unset($row[6]);
        unset($row[7]);
      }


      $build['week_' . $week_id] = [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => [$row],
      ];
    }

    $build['disclaimer'] = [
      '#type' => 'html_tag',
      '#tag' => 'em',
      '#value' => $this->t('Deviations from schema may occur.'),
    ];

    $cache->applyTo($build);
    return $build;
  }


  public function getCacheTags() {
    return Cache::mergeTags(['school_week_deviation_list', 'ssr_schema_entry_list', 'ssr_calendar_event_list'], parent::getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(['route', 'url.query_args:from', 'url.query_args:to'], parent::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if (!ssr_use_schema()) {
      return AccessResult::forbidden();
    }

    $user = $this->routeMatch->getParameter('user');
    if (!$user instanceof UserInterface || !$user->hasRole('student')) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    return $user->access('view', $account, TRUE)->addCacheContexts(['route', 'user']);
  }

}
