<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_core\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show attendance report in percent.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("attendance_report_student")
 */
class AttendanceReportStudent extends FieldPluginBase {

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\simple_school_reports_attendance_analyse\Service\AttendanceAnalyseServiceInterface|null
   */
  protected $attendanceAnalyseService;

  /**
   * @var \Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface
   */
  protected $userMetaDataService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    $instance->userMetaDataService = $container->get('simple_school_reports_core.user_meta_data');

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $container->get('module_handler');

    $instance->attendanceAnalyseService = $module_handler->moduleExists('simple_school_reports_attendance_analyse') ? $container->get('simple_school_reports_attendance_analyse.attendance_analyse_service') : NULL;
    return $instance;
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
    $this->ensureMyTable();

    $from = $this->currentRequest->get('from', 0);
    $to = $this->currentRequest->get('to', 0);

    $order = !empty($this->view?->sort['attendance_report_student']) && !empty($this->view->sort['attendance_report_student']->options['order']) ? $this->view->sort['attendance_report_student']->options['order'] : 'asc';
    if ($order_field = $this->view->getRequest()->query?->get('order')) {
      if ($order_field === 'attendance_report_student') {
        $order = $this->view->getRequest()->query?->get('sort') === 'asc' ? 'asc' : 'desc';
      }
    }

    // Set the default value last.
    $default_value = strtolower($order) === 'asc' ? '11000000' : '-1';

    $calculated_value = [];
    if ($from > 0 && $to > 0 && $this->attendanceAnalyseService) {
      $from = (new \DateTime())->setTimestamp($from);
      $to = (new \DateTime())->setTimestamp($to);
      $calculated_value = $this->attendanceAnalyseService->getAttendanceStatisticsViewsSortSource($from, $to);
    }
    if (empty($calculated_value)) {
      $calculated_value = [0 => [0]];
    }
    $cases = 'CASE ';

    foreach ($calculated_value as $value => $uids) {
      $cases .= 'WHEN uid IN (' . implode(', ', $uids) . ') THEN ' . $value . ' ';
    }
    $cases .= 'ELSE ' . $default_value . ' END';

    $this->query->addField(NULL, $cases, 'car');
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $values->uid ?? -1;

    $build = [];

    if (!$this->attendanceAnalyseService) {
      $build['value'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [],
          'title' => $this->t('Module is not enabled.'),
        ],
        '#value' => '-',
      ];
    }
    else {
      $from = (new \DateTime())->setTimestamp($this->currentRequest->get('from', 0));
      $to = (new \DateTime())->setTimestamp($this->currentRequest->get('to', 0));

      $user_grade_from = $this->userMetaDataService->getUserGrade($uid, $from);
      $user_grade_now = $this->userMetaDataService->getUserGrade($uid);

      $stats = $this->attendanceAnalyseService->getAttendanceStatistics($uid, $from, $to);

      $is_current_grade = $user_grade_from === $user_grade_now;


      $title = $this->t('No attendance data to analyse.');
      $value = '-';
      if ($stats['total'] > 0) {
        $value = round($stats['attended'] / $stats['total'] * 100, 1);
        $valid_absence_time = $stats['valid_absence'] + $stats['leave_absence'] + $stats['reported_absence'];
        $title = '';
        if (!$is_current_grade) {
          $grade_display = simple_school_reports_core_allowed_user_grade()[$user_grade_from] ?? '?';
          $title = $this->t('Grade @grade', ['@grade' => $grade_display]) . ': ';
        }

        $title .= $this->t('Attending: @attended %, Valid absence: @valid_absence %, Invalid absence: @invalid_absence %', [
          '@attended' => $value,
          '@valid_absence' =>round($valid_absence_time / $stats['total'] * 100, 1),
          '@invalid_absence' => round($stats['invalid_absence'] / $stats['total'] * 100, 1),
        ]);
      }

      $build['value'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => [],
          'title' => $title,
        ],
        '#value' => is_numeric($value) ? $value . ' %' : $value,
      ];
    }

    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['current_day', 'url.query_args']);
    $cache->addCacheTags(['school_week_list', 'node_list:day_absence', 'node_list:course_attendance_report', 'school_week_deviation_list', 'ssr_school_week_per_grade',]);
    $cache->applyTo($build);
    return $build;
  }

  public function clickSort($order) {
    $this->query->addOrderBy(NULL, NULL, $order, 'car');
  }

}
