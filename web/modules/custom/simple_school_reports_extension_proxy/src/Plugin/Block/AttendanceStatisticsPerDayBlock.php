<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Couchbase\RegexpSearchQuery;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_attendance_analyse\Service\AttendanceAnalyseServiceInterface;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a AttendanceStatisticsPerDayBlock for the current student tab.
 *
 * @Block(
 *  id = "attendance_statistics_per_day",
 *  admin_label = @Translation("Attendance statistics per day"),
 * )
 */
class AttendanceStatisticsPerDayBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\simple_school_reports_attendance_analyse\Service\AttendanceAnalyseServiceInterface
   */
  protected $attendanceAnalyseService;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface
   */
  protected $userMetaDataService;

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
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
    RequestStack $request_stack,
    UserMetaDataServiceInterface $user_meta_data_service,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->userMetaDataService = $user_meta_data_service;
    if ($module_handler->moduleExists('simple_school_reports_attendance_analyse')) {
      $this->attendanceAnalyseService = \Drupal::service('simple_school_reports_attendance_analyse.attendance_analyse_service');
    }
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
      $container->get('simple_school_reports_core.user_meta_data'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route', 'current_day', 'url.query_args:from', 'url.query_args:to']);
    $cache->addCacheTags(['school_week_list', 'node_list:day_absence', 'node_list:course_attendance_report', 'school_week_deviation_list', 'ssr_school_week_per_grade', 'ssr_schema_entry_list', 'ssr_calendar_event_list']);
    $build = [
      '#markup' => '<em>' . $this->t('No data available.') . '</em>',
    ];

    $user = $this->routeMatch->getParameter('user');
    if (!$user instanceof UserInterface) {
      $cache->applyTo($build);
      return $build;
    }
    $cache->addCacheableDependency($user);

    $this_to = new \DateTime();
    $this_to->setTime(23, 59, 59);

    $uid = $user->id();
    $from = $this->currentRequest->get('from');
    $to = min($this->currentRequest->get('to'), $this_to->getTimestamp());

    if (!$uid || !$from || !$to || $from > $to) {
      $cache->applyTo($build);
      return $build;
    }

    $from_time_object = (new \DateTime())->setTimestamp($from);
    $to_time_object = (new \DateTime())->setTimestamp($to);

    $school_week = $this->attendanceAnalyseService->getSchoolWeek($user->id(), $from_time_object);
    if (!$school_week) {
      $cache->applyTo($build);
      return $build;
    }

    $build = [];

    $user_grade_from = $this->userMetaDataService->getUserGrade($uid, $from_time_object);
    $user_grade_to = $this->userMetaDataService->getUserGrade($uid, $to_time_object);
    $user_grade_now = $this->userMetaDataService->getUserGrade($uid);
    $not_current_grade = $user_grade_from !== $user_grade_now;

    if ($user_grade_from !== $user_grade_to) {
      $build['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('The selected period may span of multiple grades, attendance statistics may not be accurate.'),
      ];

      $cache->applyTo($build);
      return $build;
    }

    $per_day_data = $this->attendanceAnalyseService->getAttendanceStatistics($uid, $from_time_object, $to_time_object)['per_day'];

    $headers = [
      'week' => $this->t('Week'),
      1 => $this->t('Monday'),
      2 => $this->t('Tuesday'),
      3 => $this->t('Wednesday'),
      4 => $this->t('Thursday'),
      5 => $this->t('Friday'),
      6 => $this->t('Saturday'),
      7 => $this->t('Sunday'),
    ];

    $use_weekend = FALSE;

    $rows = [];
    $current_day = $from_time_object;

    $safe_break = 0;
    while ($current_day <= $to_time_object || $safe_break > 1080) {
      $day = (int) $current_day->format('N');
      $year = $current_day->format('Y');
      $week_number = $current_day->format('W');
      $row_key = $year . '-' . $week_number;

      if (empty($rows[$row_key])) {
        $rows[$row_key] = [
          'week' => $week_number,
          1 => [],
          2 => [],
          3 => [],
          4 => [],
          5 => [],
          6 => [],
          7 => [],
        ];
      }

      $day_stat_classes = 'attendance-day-stats';
      $day_stat_value = '';

      $data = $per_day_data[$current_day->format('Y-m-d')] ?? [];

      if (empty($data) || $data['total'] <= 0) {
        $day_stat_classes .= ' no-data';
      }
      else {
        if ($day === 6 || $day === 7) {
          $use_weekend = TRUE;
        }
        $total = $data['total'];
        $attended = $data['attended'];
        $valid_absence = $data['valid_absence'] + $data['leave_absence'] + $data['reported_absence'];
        $invalid_absence = $data['invalid_absence'];

        $attended_percent = round(($attended / $total) * 100, 1);
        $valid_absence_percent = round(($valid_absence / $total) * 100, 1);
        $invalid_absence_percent = round(($invalid_absence / $total) * 100, 1);

        $day_stat_class = 'attendance-day-stats--ok';
        if ($attended_percent < 80) {
          $day_stat_class = 'attendance-day-stats--warning';
        }
        if ($attended_percent < 20) {
          $day_stat_class = 'attendance-day-stats--danger';
        }
        $day_stat_classes .= ' ' . $day_stat_class;

        $day_stat_value = $this->t('A: @attended % VA: @valid_absence % IA: @invalid_absence %', [
          '@attended' => $attended_percent,
          '@valid_absence' => $valid_absence_percent,
          '@invalid_absence' => $invalid_absence_percent,
        ]);
        $day_stat_value = str_replace(' %', ' %<br>', $day_stat_value);
      }

      $day_lessons = [];

      if (!empty($data) && !empty($data['lessons'])) {
        foreach ($data['lessons'] as $lesson_key => $lesson) {
          $lesson_length = $lesson['length'] ?? 0;
          if ($lesson_length <= 0) {
            continue;
          }

          if (empty($lesson['from']) || empty($lesson['to'])) {
            continue;
          }

          $lesson_type = $lesson['type'] ?? '?';
          if ($lesson_type !== 'reported' && $lesson_type !== 'not_reported') {
            continue;
          }

          $name = $lesson['subject'] ?? 'n/a';

          if ($name === 'CBT' || $name === 'BT') {
            if ($lesson_type === 'not_reported') {
              continue;
            }
            $name = 'BT';
          }

          // If name included ':' use only the last part of the name.
          if (str_contains($name, ':')) {
            $name = explode(':', $name);
            $name = array_pop($name);
          }

          $from_time = (new \DateTime())->setTimestamp($lesson['from'])->format('H:i');
          $to_time = (new \DateTime())->setTimestamp($lesson['to'])->format('H:i');

          $title = $name . ' (' . $from_time . ' - ' . $to_time . ')';

          $attended = $lesson['attended'] ?? 0;
          $valid_absence = ($lesson['valid_absence'] ?? 0) + ($lesson['leave_absence'] ?? 0) + ($lesson['reported_absence'] ?? 0);
          $invalid_absence = $lesson['invalid_absence'] ?? 0;
          $not_reported = 0;

          if ($lesson_type === 'not_reported') {
            $attended = 0;
            $valid_absence = 0;
            $invalid_absence = 0;
            $not_reported = $lesson_length;
          }

          if ($attended + $valid_absence + $invalid_absence + $not_reported !== $lesson_length) {
            $lesson_length = $attended + $valid_absence + $invalid_absence + $not_reported;
            if ($lesson_length <= 0) {
              continue;
            }
          }

          $attended_percent = round(($attended / $lesson_length) * 100, 1);
          $valid_absence_percent = round(($valid_absence / $lesson_length) * 100, 1);
          $invalid_absence_percent = round(($invalid_absence / $lesson_length) * 100, 1);
          $not_reported_percent = round(($not_reported / $lesson_length) * 100, 1);

          if ($lesson_type === 'not_reported') {
            $title .= ' - ' . $this->t('Not reported');
          }
          else {
            $title .= ' - ' . $this->t('A: @attended % VA: @valid_absence % IA: @invalid_absence %', [
                '@attended' => $attended_percent,
                '@valid_absence' => $valid_absence_percent,
                '@invalid_absence' => $invalid_absence_percent,
              ]);
          }

          $day_lessons[$lesson_key]['wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['attendance-day-lesson-wrapper'],
              'title' => $title,
            ],
          ];

          $day_lessons[$lesson_key]['wrapper']['svg_target'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['attendance-day-lesson-svg'],
            ],
          ];

          $day_lessons[$lesson_key]['wrapper']['stat'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['attendance-day-lesson-stat'],
              'data-attended' => $attended_percent,
              'data-valid-absence' => $valid_absence_percent,
              'data-invalid-absence' => $invalid_absence_percent,
              'data-not-reported' => $not_reported_percent,
            ],
            'value' => [
              '#markup' => $name,
            ],
          ];
        }
      }


      $rows[$row_key][$day]['data']['day_label'] = [
        '#markup' => '<div><strong>' . $current_day->format('j/n') . '</strong></div>',
      ];

      $rows[$row_key][$day]['data']['day_stats'] = [
        '#markup' => '<div class="' . $day_stat_classes . '">' . $day_stat_value . '</div>',
      ];
      if (!empty($day_lessons)) {
        if ($day === 6 || $day === 7) {
          $use_weekend = TRUE;
        }
        $rows[$row_key][$day]['data']['lessons_wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['attendance-day-lessons'],
          ],
        ];
        $rows[$row_key][$day]['data']['lessons_wrapper']['lessons'] = $day_lessons;
      }

      $current_day->modify('+1 day');
      $safe_break++;
    }

    ksort($rows);

    if (!$use_weekend) {
      unset($headers[6]);
      unset($headers[7]);
      foreach ($rows as &$row) {
        unset($row[6]);
        unset($row[7]);
      }
    }

    $build['stat_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Attendance statistics per day'),
      '#open' => count($rows) <= 6,
    ];

    $build['stat_wrapper']['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['stats-day-table'],
      ],
    ];

    if ($not_current_grade) {
      $grade_display = SchoolGradeHelper::getSchoolGradesMapAll()[$user_grade_from] ?? '?';
      $build['stat_wrapper']['not_current_grade_info'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => '*' . $this->t('Student grade are assumed to be @grade for this period which may affect the school day length in the analyse.', ['@grade' => $grade_display]),
      ];
    }

    $build['#attached']['library'][] = 'simple_school_reports_extension_proxy/attendance_statistics_per_day_block';
    $build['#attributes']['class'][] = 'attendance-statistics-per-day-block';

    $cache->applyTo($build);
    return $build;
  }

  public function getCacheTags() {
    return Cache::mergeTags(['school_week_list', 'node_list:day_absence', 'node_list:course_attendance_report', 'school_week_deviation_list', 'ssr_school_week_per_grade', 'ssr_schema_entry_list', 'ssr_calendar_event_list'], parent::getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(['route', 'current_day', 'url.query_args:from', 'url.query_args:to'], parent::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if (!$this->moduleHandler->moduleExists('simple_school_reports_attendance_analyse')) {
      return AccessResult::forbidden();
    }

    $user = $this->routeMatch->getParameter('user');
    if (!$user instanceof UserInterface || !$user->hasRole('student')) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    return $user->access('update', $account, TRUE)->addCacheContexts(['route', 'user']);
  }

}
