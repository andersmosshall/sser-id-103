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
    $cache->addCacheTags(['school_week_list', 'node_list:day_absence', 'node_list:course_attendance_report', 'school_week_deviation_list', 'ssr_school_week_per_grade',]);
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
        $first_day_of_week = clone $current_day;
        $first_day_of_week->modify('monday this week');

        $last_day_of_week = clone $current_day;
        $last_day_of_week->modify('sunday this week');

        $week_value = $this->t('w.@week (@from - @to)', [
          '@week' => $week_number,
          '@from' => $first_day_of_week->format('j/n'),
          '@to' => $last_day_of_week->format('j/n'),
        ]);

        $rows[$row_key] = [
          'week' => $week_value,
          1 => '',
          2 => '',
          3 => '',
          4 => '',
          5 => '',
          6 => '',
          7 => '',
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

      $rows[$row_key][$day] = [
        'data' => [
          '#markup' => '<div class="' . $day_stat_classes . '">' . $day_stat_value . '</div>',
        ],
      ];

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
      $grade_display = simple_school_reports_core_allowed_user_grade()[$user_grade_from] ?? '?';
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
    return Cache::mergeTags(['school_week_list', 'node_list:day_absence', 'node_list:course_attendance_report', 'school_week_deviation_list', 'ssr_school_week_per_grade',], parent::getCacheTags());
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
