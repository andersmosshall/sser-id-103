<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_extension_proxy\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show attendance report in percent and time for a period.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("attendance_period_report_student")
 */
class AttendancePeriodReportStudent extends FieldPluginBase {

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
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['report_type'] = ['default' => 'attendance'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [
      'attended' => $this->t('Attendance'),
      'valid_absence' => $this->t('Valid absence'),
      'invalid_absence' => $this->t('Invalid absence'),
      'total_absence' => $this->t('Total absence'),
    ];

    $form['report_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select report type'),
      '#options' => $options,
      '#default_value' => $this->options['report_type'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // @todo Implement this when needed.

//    $this->ensureMyTable();
//    $from = $this->currentRequest->get('from', 0);
//    $to = $this->currentRequest->get('to', 0);
//
//    $order = !empty($this->view?->sort['attendance_period_report_student']) && !empty($this->view->sort['attendance_period_report_student']->options['order']) ? $this->view->sort['attendance_period_report_student']->options['order'] : 'asc';
//    if ($order_field = $this->view->getRequest()->query?->get('order')) {
//      if ($order_field === 'attendance_period_report_student') {
//        $order = $this->view->getRequest()->query?->get('sort') === 'asc' ? 'asc' : 'desc';
//      }
//    }
//
//    // Set the default value last.
//    $default_value = strtolower($order) === 'asc' ? '11000000' : '-1';
//
//    $calculated_value = [];
//    if ($from > 0 && $to > 0 && $this->attendanceAnalyseService) {
//      $from = (new \DateTime())->setTimestamp($from);
//      $to = (new \DateTime())->setTimestamp($to);
//      $calculated_value = $this->attendanceAnalyseService->getAttendanceStatisticsViewsSortSource($from, $to);
//    }
//    if (empty($calculated_value)) {
//      $calculated_value = [0 => [0]];
//    }
//    $cases = 'CASE ';
//
//    foreach ($calculated_value as $value => $uids) {
//      $cases .= 'WHEN uid IN (' . implode(', ', $uids) . ') THEN ' . $value . ' ';
//    }
//    $cases .= 'ELSE ' . $default_value . ' END';
//
//    $this->query->addField(NULL, $cases, 'car');
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


      try {
        $from = $this->currentRequest->get('from');
        $to = $this->currentRequest->get('to');

        if (!$from || !$to) {
          throw new \RuntimeException('missing properties');
        }

        $from = new \DateTime($from . ' 00:00:00');
        $to = new \DateTime($to . ' 23:59:59');
      }
      catch (\Exception $e) {
        $from = NULL;
        $to = NULL;
      }


      $title = $this->t('No attendance data to analyse.');
      $value = '-';
      $stats = [];
      $stats['total'] = 0;
      $is_current_grade = TRUE;

      if ($from && $to) {
        $user_grade_from = $this->userMetaDataService->getUserGrade($uid, $from);
        $user_grade_now = $this->userMetaDataService->getUserGrade($uid);
        $stats = $this->attendanceAnalyseService->getAttendanceStatistics($uid, $from, $to);
        $is_current_grade = $user_grade_from === $user_grade_now;
      }

      if ($stats['total'] > 0) {

        switch ($this->options['report_type']) {
          case 'attended':
            $value = round($stats['attended'] / $stats['total'] * 100, 1);
            break;
          case 'valid_absence':
            $value = round(($stats['valid_absence'] + $stats['leave_absence'] + $stats['reported_absence']) / $stats['total'] * 100, 1);
            break;
          case 'invalid_absence':
            $value = round($stats['invalid_absence'] / $stats['total'] * 100, 1);
            break;
          case 'total_absence':
            $value = round(($stats['valid_absence'] + $stats['leave_absence'] + $stats['reported_absence'] + $stats['invalid_absence']) / $stats['total'] * 100, 1);
            break;
          default:
            break;
        }

        $title = '';
        if (!$is_current_grade) {
          $grade_display = simple_school_reports_core_allowed_user_grade()[$user_grade_from] ?? '?';
          $title = $this->t('Grade @grade', ['@grade' => $grade_display]) . ': ';
        }
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
    $cache->addCacheTags(['school_week_list', 'node_list:day_absence', 'node_list:course_attendance_report', 'school_week_deviation_list', 'ssr_school_week_per_grade', 'ssr_schema_entry_list', 'ssr_calendar_event_list']);
    $cache->applyTo($build);
    return $build;
  }

//  public function clickSort($order) {
//    $this->query->addOrderBy(NULL, NULL, $order, 'car');
//  }

}
