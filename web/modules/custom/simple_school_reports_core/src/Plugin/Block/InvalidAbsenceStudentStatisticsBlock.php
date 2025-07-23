<?php

namespace Drupal\simple_school_reports_core\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'InvalidAbsenceStudentStatisticsBlock' block.
 *
 * @Block(
 *  id = "invalid_absence_student_statistics",
 *  admin_label = @Translation("Invalid absence student statistics"),
 * )
 */
class InvalidAbsenceStudentStatisticsBlock extends StatisticsBlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  protected $calculatedData;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\simple_school_reports_core\Service\SchoolSubjectServiceInterface
   */
  protected $schoolSubjectService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->schoolSubjectService = $container->get('simple_school_reports_core.school_subjects');
    return $instance;
  }

  public function getLibraries() : array {
    return ['simple_school_reports_core/invalid_absence_student_statistics'];
  }

  protected function getCalculatedData() {
    if (!is_array($this->calculatedData)) {
      $calculated_data = [];
      $calculated_data['negative_absence_time'] = 0;
      if ($user = $this->currentRouteMatch->getParameter('user')) {
        $uid = $user->id();
        $from = $this->currentRequest->get('from');
        $to = $this->currentRequest->get('to');
        if ($uid && $from && $to) {
          $subject_map = $this->schoolSubjectService->getSchoolSubjectOptionList(NULL, TRUE);
          asort($subject_map);
          $absence_data = [];

          $attendance_report_nids = $this->entityTypeManager->getStorage('node')->getQuery()
            ->condition('type', 'course_attendance_report')
            ->condition('field_class_start', $to, '<')
            ->condition('field_class_end', $from, '>')
            ->accessCheck(FALSE)
            ->execute();

          if (!empty($attendance_report_nids)) {
            $query = $this->connection->select('paragraph__field_invalid_absence', 'ia');
            $query->innerJoin('paragraph__field_student', 's', 's.entity_id = ia.entity_id');
            $query->innerJoin('paragraphs_item_field_data', 'd', 'd.id = ia.entity_id');
            $query->innerJoin('paragraph__field_subject', 'sub', 'sub.entity_id = ia.entity_id');
            $query->condition('ia.bundle', 'student_course_attendance')
              ->condition('s.field_student_target_id', $uid)
              ->condition('d.parent_id', $attendance_report_nids, 'IN')
              ->fields('ia',['field_invalid_absence_value'])
              ->fields('sub',['field_subject_target_id']);

            $results = $query->execute();
            foreach ($results as $result) {
              $subject_id = $result->field_subject_target_id;
              if (!isset($subject_map[$subject_id])) {
                continue;
              }
              if (!isset($absence_data[$subject_id])) {
                $absence_data[$subject_id] = [
                  'label' => $subject_map[$subject_id],
                  'data' => 0,
                ];
              }
              if ((int) $result->field_invalid_absence_value > 0) {
                $absence_data[$subject_id]['data'] += $result->field_invalid_absence_value;
              }
              if ((int) $result->field_invalid_absence_value < 0) {
                $calculated_data['negative_absence_time'] += abs($result->field_invalid_absence_value);
              }
            }

            foreach ($subject_map as $subject_id => $subject_name) {
              if (isset($absence_data[$subject_id]['data']) && $absence_data[$subject_id]['data'] > 0) {
                $calculated_data['subject_absence'][$subject_id] = $absence_data[$subject_id];
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

    if (empty($calculated_data['subject_absence'])) {
      return [];
    }

    $data = [
      'labels' => [],
      'datasets' => [],
    ];

    $dataset = [
      'label' => (string) $this->t('Absence in minutes'),
      'data' => [],
      'backgroundColor' => [
        'rgba(0, 60, 197, 0.5)',
      ],
    ];

    foreach ($calculated_data['subject_absence'] as $item) {
      $data['labels'][] = $item['label'];
      $dataset['data'][] = $item['data'];
    }

    $data['datasets'][] = $dataset;

    return $data;
  }
  public function getGraphDataType() : string {
    return 'invalid_absence_student_statistics';
  }

  public function getTable(): array {

    $calculated_data = $this->getCalculatedData();

    $table = [];
    $table['table'] = [
      '#type' => 'table',
      '#header' => [
        'subject' => $this->t('School subject'),
        'value' => $this->t('Invalid absence'),
        'proportion' => $this->t('Proportion'),
      ],
      '#empty' => $this->t('No attendance reports to summarize for selected period.'),
    ];

    if (!empty($calculated_data['subject_absence'])) {
      $total = 0;

      foreach ($calculated_data['subject_absence'] as $item) {
        $total += $item['data'];
      }

      foreach ($calculated_data['subject_absence'] as $key => $item) {
        $table['table'][$key]['subject'] = ['#markup' => $item['label']];
        $table['table'][$key]['value'] = ['#markup' => $item['data'] . ' ' . $this->t('min')];
        $table['table'][$key]['proportion'] = ['#markup' => $item['data'] && $total ? round($item['data'] / $total, 3) * 100 . ' %' : ''];
      }

      $key = 'sum';
      $table['table'][$key]['subject'] = [
        '#type' => 'html_tag',
        '#tag' => 'b',
        '#value' => $this->t('Sum'),
      ];
      $table['table'][$key]['value'] = [
        '#type' => 'html_tag',
        '#tag' => 'b',
        '#value' => $total . ' min',
      ];
      $table['table'][$key]['proportion'] = [
        '#type' => 'html_tag',
        '#tag' => 'b',
        '#value' => '100 %',
      ];


      $table['suffix'] = [
        '#type' => 'html_tag',
        '#tag' => 'em',
        '#value' => $this->t('The proportion is compared to total invalid absence.'),
      ];

      $context = [
        'from' => $this->currentRequest->get('from', 0),
        'to' => $this->currentRequest->get('from', 0),
        'uid' => $this->currentRouteMatch->getParameter('user')->id(),
        'sum' => $total,
        'negative_absence_time' => $calculated_data['negative_absence_time'],
      ];

      $this->moduleHandler->alter('invalid_absence_student_statistics_table', $table, $context);
    }

    return $table;
  }

  protected function getCacheObject() {
    if (!$this->cacheObject) {
      $cache = parent::getCacheObject();
      $user = $this->currentRouteMatch->getParameter('user');
      $cache->addCacheableDependency($user);
      $cache->addCacheTags(['node_list:course_attendance_report']);
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
