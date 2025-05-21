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
 * Provides a AttendanceStatisticsBlock for the current student tab.
 *
 * @Block(
 *  id = "attendance_statistics",
 *  admin_label = @Translation("Attendance statistics"),
 * )
 */
class AttendanceStatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $build = [];

    $user = $this->routeMatch->getParameter('user');
    if (!$user instanceof UserInterface) {
      $cache->applyTo($build);
      return $build;
    }
    $cache->addCacheableDependency($user);

    $uid = $user->id();
    $from = $this->currentRequest->get('from');
    $to = $this->currentRequest->get('to');
    if (!$uid || !$from || !$to) {
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

    $user_grade_from = $this->userMetaDataService->getUserGrade($uid, $from_time_object);
    $user_grade_to = $this->userMetaDataService->getUserGrade($uid, $to_time_object);

    if ($user_grade_from !== $user_grade_to) {
      $build['info'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('The selected period may span of multiple grades, attendance statistics may not be accurate.'),
      ];

      $cache->applyTo($build);
      return $build;
    }

    $user_grade_now = $this->userMetaDataService->getUserGrade($uid);
    $not_current_grade = $user_grade_from !== $user_grade_now;

    $data = $this->attendanceAnalyseService->getAttendanceStatistics($uid, $from_time_object, $to_time_object);

    $headers = [
      'type' => '',
      'proportion' => $this->t('Proportion'),
      'time' => $this->t('Time'),
    ];

    $rows = [];

    $rows['attendance']['data'] = [
      'type' => $this->t('Attendance') . ($not_current_grade ? '*' : ''),
      'proportion' => $data['total'] ? round(($data['attended'] / $data['total']) * 100, 1) . ' %' : '-',
      'time' => $this->getTimeString($data['attended']),
    ];

    $valid_absence_time = $data['valid_absence'] + $data['leave_absence'] + $data['reported_absence'];
    $rows['valid_absence']['data'] = [
      'type' => $this->t('Valid absence'),
      'proportion' => $data['total'] ? round(($valid_absence_time / $data['total']) * 100, 1) . ' %' : '-',
      'time' => $this->getTimeString($valid_absence_time),
    ];
    $rows['reported']['data'] = [
      'type' => $this->t('Reported absence'),
      'proportion' => $data['total'] ? round(($data['reported_absence'] / $data['total']) * 100, 1) . ' %' : '-',
      'time' => $this->getTimeString($data['reported_absence']),
    ];
    $rows['leave']['data'] = [
      'type' => $this->t('Leave absence'),
      'proportion' => $data['total'] ? round(($data['leave_absence'] / $data['total']) * 100, 1) . ' %' : '-',
      'time' => $this->getTimeString($data['leave_absence']),
    ];
    $rows['valid_absence_course']['data'] = [
      'type' => $this->t('Valid absence from course'),
      'proportion' => $data['total'] ? round(($data['valid_absence'] / $data['total']) * 100, 1) . ' %' : '-',
      'time' => $this->getTimeString($data['valid_absence']),
    ];

    $rows['invalid_absence']['data'] = [
      'type' => $this->t('Invalid absence'),
      'proportion' => $data['total'] ? round(($data['invalid_absence'] / $data['total']) * 100, 1) . ' %' : '-',
      'time' => $this->getTimeString($data['invalid_absence']),
    ];
    $rows['invalid_absence_course']['data'] = [
      'type' => $this->t('Invalid absence from course'),
      'proportion' => $data['total'] ? round(($data['invalid_absence'] / $data['total']) * 100, 1) . ' %' : '-',
      'time' => $this->getTimeString($data['invalid_absence']),
    ];

    $rows['total']['data'] = [
      'type' => $this->t('Total school time'),
      'proportion' => '-',
      'time' => $this->getTimeString($data['total']),
    ];
    $rows['total']['class'] = ['stats-total'];

    // Details rows.
    $details_rows = [
      'reported',
      'leave',
      'valid_absence_course',
      'invalid_absence_course',
    ];

    foreach ($details_rows as $detail_row) {
      $rows[$detail_row]['class'] = ['stats-details'];
    }

    $build['details_toggle'] = [
      '#markup' => '<a class="ssr-details-toggle" data-toggle-selector=".attendance-stats">' . $this->t('Show details') . '</a>',
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['attendance-stats', 'stats-table'],
      ],
    ];


    if ($not_current_grade) {
      $grade_display = simple_school_reports_core_allowed_user_grade()[$user_grade_from] ?? '?';
      $build['not_current_grade_info'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => '*' . $this->t('Student grade are assumed to be @grade for this period which may affect the school day length in the analyse.', ['@grade' => $grade_display]),
      ];
    }

    $build['school_week_info_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['attendance-stats', 'school-week-info-wrapper'],
      ],
    ];

    $build['school_week_info_wrapper']['school_week_info'] = [
      '#type' => 'details',
      '#title' => $this->t('School week'),
      '#open' => FALSE,
      '#attributes' => [
        'class' => ['stats-details', 'school-week-info'],
      ],
      'value' => $school_week->toTable(TRUE),
    ];

    $build['#attached']['library'][] = 'simple_school_reports_core/details_toggle';
    $build['#attached']['library'][] = 'simple_school_reports_extension_proxy/attendance_statistics_block';
    $build['#attributes']['class'][] = 'attendance-statistics-block';

    $cache->applyTo($build);
    return $build;
  }

  protected function getTimeString(int $length): string {

    if ($length === 0) {
      return '-';
    }

    $hours = floor($length / 3600);
    $min = round(($length % 3600) / 60);

    if ($hours < 10) {
      $hours = '0' . $hours;
    }
    else {
      $hours = number_format($hours, 0, ',', ' ');
    }
    if ($min < 10) {
      $min = '0' . $min;
    }

    return $hours . ':' . $min;
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
