<?php

namespace Drupal\simple_school_reports_core\Plugin\Block;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\AbsenceDayHandler;

/**
 * Provides a 'AbsenceDayStudentStatisticsBlock' block.
 *
 * @Block(
 *  id = "absence_day_student_statistics",
 *  admin_label = @Translation("Absence day student statistics"),
 * )
 */
class AbsenceDayStudentStatisticsBlock extends StatisticsBlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  protected $calculatedData;

  public function getLibraries() : array {
    return ['simple_school_reports_core/absence_day_student_statistics'];
  }

  protected function getCalculatedData() {
    if (!is_array($this->calculatedData)) {
      $calculated_data = [];
      if ($user = $this->currentRouteMatch->getParameter('user')) {
        /** @var \Drupal\simple_school_reports_entities\SchoolWeekInterface|null $school_week */
        $school_week = NULL;
        $uid = $user->id();
        $from = $this->currentRequest->get('from');
        $to = $this->currentRequest->get('to');
        if ($uid && $from && $to) {
          $absence_nids = [];
          $attendance_statistics = [];
          if ($this->moduleHandler->moduleExists('simple_school_reports_attendance_analyse')) {
            /** @var \Drupal\simple_school_reports_attendance_analyse\Service\AttendanceAnalyseServiceInterface $service */
            $service = \Drupal::service('simple_school_reports_attendance_analyse.attendance_analyse_service');
            $from_time_object = (new \DateTime())->setTimestamp($from);
            if ($school_week = $service->getSchoolWeek($uid, $from_time_object)) {
              $to_time_object = (new \DateTime())->setTimestamp($to);
              $attendance_statistics = $service->getAttendanceStatistics($uid, $from_time_object, $to_time_object, TRUE);
            }
          }

          if (empty($attendance_statistics) || $attendance_statistics['total'] === 0) {
            $absence_nids = AbsenceDayHandler::getAbsenceNodesFromPeriod([$uid], $from, $to, TRUE);
          }


          if (!empty($absence_nids) || !empty($attendance_statistics['reported_absence']) || !empty($attendance_statistics['leave_absence'])) {
            $calculated_data[1] = [
              'label' => $this->t('Monday'),
              'reported' => 0,
              'leave' => 0,
            ];
            $calculated_data[2] = [
              'label' => $this->t('Tuesday'),
              'reported' => 0,
              'leave' => 0,
            ];
            $calculated_data[3] = [
              'label' => $this->t('Wednesday'),
              'reported' => 0,
              'leave' => 0,
            ];
            $calculated_data[4] = [
              'label' => $this->t('Thursday'),
              'reported' => 0,
              'leave' => 0,
            ];
            $calculated_data[5] = [
              'label' => $this->t('Friday'),
              'reported' => 0,
              'leave' => 0,
            ];
            $calculated_data[6] = [
              'label' => $this->t('Saturday'),
              'reported' => 0,
              'leave' => 0,
            ];
            $calculated_data[7] = [
              'label' => $this->t('Sunday'),
              'reported' => 0,
              'leave' => 0,
            ];

            // Remove non school days.
            if (empty($absence_nids) && $school_week) {
              foreach ($calculated_data as $day_key => $item) {
                if ($school_week->get('length_' . $day_key)->value > 0) {
                  continue;
                }

                unset($calculated_data[$day_key]);
              }
            }
          }

          if (!empty($absence_nids)) {
            $query = $this->connection->select('node__field_absence_type', 't');
            $query->innerJoin('node__field_absence_from', 'af', 'af.entity_id = t.entity_id');
            $query->innerJoin('node__field_absence_to', 'at', 'at.entity_id = t.entity_id');
            $query->condition('t.entity_id', $absence_nids, 'IN')
              ->fields('t',['field_absence_type_value'])
              ->fields('af',['field_absence_from_value'])
              ->fields('at',['field_absence_to_value']);
            $results = $query->execute();

            foreach ($results as $result) {
              $value = $result->field_absence_to_value - $result->field_absence_from_value;
              if ($value > 0) {
                $date = new DrupalDateTime();
                $date->setTimestamp($result->field_absence_from_value);
                $day = $date->format('N');
                $type = $result->field_absence_type_value;
                if (isset($calculated_data[$day][$type])) {
                  $calculated_data[$day][$type] += $value;
                }
              }
            }

            // Convert to days.
            foreach ($calculated_data as &$data) {
              $data['reported'] = round($data['reported'] / 86400, 2);
              $data['leave'] = round($data['leave'] / 86400, 2);
            }
          }
          elseif (!empty($attendance_statistics['reported_absence']) || !empty($attendance_statistics['leave_absence']) && $school_week) {
            $types = [
              'reported_absence' => 'reported',
              'leave_absence' => 'leave',
            ];

            foreach ($types as $type_index => $type) {
              if (!empty($attendance_statistics[$type_index])) {
                for ($day_index = 1; $day_index <= 7; $day_index++) {
                  if (!isset($calculated_data[$day_index])) {
                    continue;
                  }

                  $school_day_length = $school_week->get('length_' . $day_index)->value;
                  if (!($school_day_length > 0)) {
                    continue;
                  }

                  $school_day_length = $school_day_length * 60;

                  $stat_key = $type_index . '_' . $day_index;
                  if (!empty($attendance_statistics[$stat_key])) {
                    $calculated_data[$day_index][$type] = round($attendance_statistics[$stat_key] / $school_day_length, 2);
                  }
                }
              }
            }
          }
        }
      }

      $this->calculatedData = $calculated_data;
    }
    return $this->calculatedData;
  }

  public function getGraphData() : array {
    $calculated_data = $this->getCalculatedData();

    if (empty($calculated_data)) {
      return [];
    }

    $data = [
      'labels' => [],
      'datasets' => [],
    ];

    $dataset_reported = [
      'label' => (string) $this->t('Reported absence'),
      'data' => [],
      'backgroundColor' => [
        'rgba(0, 60, 197, 0.75)',
      ],
    ];

    $dataset_leave = [
      'label' => (string) $this->t('Leave absence'),
      'data' => [],
      'backgroundColor' => [
        'rgba(0, 60, 197, 0.5)',
      ],
    ];

    foreach ($calculated_data as $item) {
      $data['labels'][] = $item['label'];
      $dataset_reported['data'][] = $item['reported'];
      $dataset_leave['data'][] = $item['leave'];
    }

    $data['datasets'][] = $dataset_reported;
    $data['datasets'][] = $dataset_leave;

    return $data;
  }
  public function getGraphDataType() : string {
    return 'absence_day_student_statistics';
  }

  public function getTable(): array {
    $calculated_data = $this->getCalculatedData();

    $table = [];
    $table['table'] = [
      '#type' => 'table',
      '#header' => [
        'subject' => $this->t('Weekday'),
        'reported' => $this->t('Reported absence'),
        'leave' => $this->t('Leave absence'),
      ],
      '#empty' => $this->t('No registered absence to summarize for selected period.'),
    ];

    $total_reported = 0;
    $total_leave = 0;

    if (!empty($calculated_data)) {
      foreach ($calculated_data as $key => $item) {
        if (is_numeric($item['reported'])) {
          $total_reported += $item['reported'];
        }

        if (is_numeric($item['leave'])) {
          $total_leave += $item['leave'];
        }

        $table['table'][$key]['subject'] = ['#markup' => $item['label']];
        $table['table'][$key]['reported'] = ['#markup' => $item['reported'] . ' ' . $this->t('days')];
        $table['table'][$key]['leave'] = ['#markup' => $item['leave'] . ' ' . $this->t('days')];
      }
    }

    $key = 'sum';
    $table['table'][$key]['subject'] = [
      '#type' => 'html_tag',
      '#tag' => 'b',
      '#value' => $this->t('Sum'),
    ];
    $table['table'][$key]['reported'] = [
      '#type' => 'html_tag',
      '#tag' => 'b',
      '#value' => $total_reported . ' ' . $this->t('days'),
    ];
    $table['table'][$key]['leave'] = [
      '#type' => 'html_tag',
      '#tag' => 'b',
      '#value' => $total_leave . ' ' . $this->t('days'),
    ];

    return $table;
  }

  protected function getCacheObject() {
    if (!$this->cacheObject) {
      $cache = parent::getCacheObject();
      $user = $this->currentRouteMatch->getParameter('user');
      $cache->addCacheableDependency($user);
      $cache->addCacheTags(['node_list:day_absence', 'school_week_list']);
      $this->cacheObject = $cache;
    }
    return $this->cacheObject;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->currentRouteMatch->getParameter('user');
    return AccessResult::allowedIf($user && $user->access('update', $account))->cachePerUser()->addCacheContexts(['route']);
  }

}
