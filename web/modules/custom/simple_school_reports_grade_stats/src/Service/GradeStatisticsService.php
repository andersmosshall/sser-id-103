<?php

namespace Drupal\simple_school_reports_grade_stats\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_extension_proxy\Service\GradeSupportServiceInterface;

/**
 * Class AbsenceStatisticsService
 *
 * @package Drupal\simple_school_reports_grade_stats\Service
 */
class GradeStatisticsService implements GradeStatisticsServiceInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var \Drupal\simple_school_reports_extension_proxy\Service\GradeSupportServiceInterface
   */
  protected $gradeSupportService;

  /**
   * @var array
   */
  protected $mandatorySubjects;

  /**
   * @var array
   */
  protected $meritMap;

  /**
   * @var array
   */
  protected $codeMap;

  /**
   * TermService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface $cache,
    GradeSupportServiceInterface $grade_support_service
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->gradeSupportService = $grade_support_service;
  }

  /**
   * @inheritDoc
   */
  public function getGradeStatistics(string $grade_round_nid) : array {
    $cid = 'grade_round_statistics:' . $grade_round_nid;
    $cache = $this->cache->get($cid);
    if ($cache && is_array($cache->data)) {
      return $cache->data;
    }

    $calculated_value = [
      'student_groups' => [],
      'male_count' => 0,
      'female_count' => 0,
      'all_count' => 0,
    ];

    $gender_map = [];
    $results = $this->connection->select('user__field_gender', 'g')
      ->fields('g', ['entity_id', 'field_gender_value'])
      ->execute();
    foreach ($results as $result) {
      $gender_map[$result->entity_id] = $result->field_gender_value;
    }

    $query = $this->connection->select('paragraphs_item', 'p');
    $query->innerJoin('paragraph__field_grade', 'g', 'g.entity_id = p.id');
    $query->innerJoin('paragraph__field_grade_round', 'round', 'round.entity_id = p.id');
    $query->innerJoin('node__field_grade_registration', 'gr', 'gr.field_grade_registration_target_id = p.id');
    $query->innerJoin('node__field_grade_subject', 'gs', 'gs.field_grade_subject_target_id = gr.entity_id');
    $query->innerJoin('paragraph__field_school_subject', 'sub', 'sub.entity_id = p.id');
    $query->leftJoin('paragraph__field_student', 's', 's.entity_id = p.id');
    $query->leftJoin('paragraph__field_exclude_reason', 'er', 'er.entity_id = p.id');
    $query->leftJoin('paragraph__field_gender', 'gen', 'gen.entity_id = p.id');

    $results = $query->condition('p.type', 'grade_registration')
      ->condition('round.field_grade_round_target_id', $grade_round_nid)
      ->fields('gen', ['field_gender_value'])
      ->fields('g', ['field_grade_target_id'])
      ->fields('sub', ['field_school_subject_target_id'])
      ->fields('gs', ['entity_id'])
      ->fields('s', ['field_student_target_id'])
      ->fields('p', ['id'])
      ->fields('er', ['field_exclude_reason_value'])
      ->execute();

    $handled_pids = [];
    $handled_student_count = [];
    $handled_subject_user = [];

    foreach ($results as $grade) {
      // Just in case.
      if (isset($handled_pids[$grade->id])) {
        continue;
      }
      $handled_pids[$grade->id] = TRUE;
      // Skip excluded.
      if ($grade->field_exclude_reason_value) {
        continue;
      }

      $student_group = $grade->entity_id;
      $student_uid = $grade->field_student_target_id;
      $subject_id = $grade->field_school_subject_target_id;

      $handled_subject_user[$subject_id][$student_uid] = TRUE;

      $gender = $this->gradeSupportService->resolveGender($student_uid, $grade);

      $student_counted = TRUE;
      if (empty($handled_student_count[$student_group][$student_uid])) {
        $student_counted = FALSE;
        $handled_student_count[$student_group][$student_uid] = TRUE;
      }

      if (!$student_counted) {
        $this->handleCount($calculated_value, $gender);
      }

      if (empty($calculated_value['student_groups'][$student_group])) {
        $calculated_value['student_groups'][$student_group] = [
          'students' => [],
          'subjects' => [],
        ];
      }

      if (!$student_counted) {
        $this->handleCount($calculated_value['student_groups'][$student_group], $gender);
      }

      // Handle per student grades.
      $this->handleStudentGrades($calculated_value['student_groups'][$student_group]['students'], $student_uid, $subject_id, $grade->field_grade_target_id, $gender);

      // Handle per subject.
      $this->handleSubjectGrades($calculated_value['student_groups'][$student_group]['subjects'], $gender, $subject_id, $grade->field_grade_target_id);
    }

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Handle default grades.
    $grade_round = $node_storage->load($grade_round_nid);
    /** @var \Drupal\node\NodeInterface $student_group */
    foreach ($grade_round->get('field_student_groups')->referencedEntities() as $student_group) {
      $group_students = array_column($student_group->get('field_student')->getValue(), 'target_id');

      if (!empty($group_students)) {
        $grade_subject_nids = array_column($student_group->get('field_grade_subject')
          ->getValue(), 'target_id');

        if (empty($grade_subject_nids)) {
          continue;
        }

        $school_subjects = $node_storage->getQuery()
          ->condition('type', 'grade_subject')
          ->exists('field_school_subject')
          ->exists('field_default_grade_round')
          ->condition('nid', $grade_subject_nids, 'IN')
          ->accessCheck(FALSE)
          ->execute();

        if (!empty($school_subjects)) {
          /** @var \Drupal\node\NodeInterface $school_subject */
          foreach ($node_storage->loadMultiple($school_subjects) as $school_subject) {
            $unhandled_student_ids = [];
            $subject_id = $school_subject->get('field_school_subject')->target_id;
            if ($subject_id) {
              foreach ($group_students as $student_uid) {
                if (!isset($handled_subject_user[$subject_id][$student_uid])) {
                  $unhandled_student_ids[] = $student_uid;
                }
              }

              if (!empty($unhandled_student_ids)) {
                $default_grade_round = $school_subject->get('field_default_grade_round')->target_id;
                if (!$default_grade_round) {
                  continue;
                }
                $default_grade_data = $this->gradeSupportService->getDefaultGradeRoundData($default_grade_round, $subject_id, $student_group->get('field_grade_system')->value, $unhandled_student_ids);
                foreach ($default_grade_data as $student_uid => $data) {

                  $gender = $data['gender'];

                  $student_counted = TRUE;
                  if (empty($handled_student_count[$student_group->id()][$student_uid])) {
                    $student_counted = FALSE;
                    $handled_student_count[$student_group->id()][$student_uid] = TRUE;
                  }

                  if (!$student_counted) {
                    $this->handleCount($calculated_value, $gender);
                  }

                  if (empty($calculated_value['student_groups'][$student_group->id()])) {
                    $calculated_value['student_groups'][$student_group->id()] = [
                      'students' => [],
                      'subjects' => [],
                    ];
                  }

                  if (!$student_counted) {
                    $this->handleCount($calculated_value['student_groups'][$student_group->id()], $gender);
                  }

                  // Handle per student grades.
                  $this->handleStudentGrades($calculated_value['student_groups'][$student_group->id()]['students'], $student_uid, $subject_id, $data['grade'], $gender);

                  // Handle per subject.
                  $this->handleSubjectGrades($calculated_value['student_groups'][$student_group->id()]['subjects'], $gender, $subject_id, $data['grade']);
                }
              }
            }
          }
        }
      }
    }


    if (!empty($calculated_value['all_count'])) {
      $this->prepareMaps();
      $this->calculateMeritAndEligibility($calculated_value);
    }

    $this->cache->set($cid, $calculated_value, Cache::PERMANENT, ['node:' . $grade_round_nid, 'user_gender_change']);
    return $calculated_value;
  }

  protected function handleCount(&$data, $gender) {
    $type_check = ['all', 'male', 'female'];
    foreach ($type_check as $type) {
      if (!isset($data[$type . '_count'])) {
        $data[$type . '_count'] = 0;
      }
    }

    $data['all_count']++;
    if ($gender && in_array($gender, $type_check)) {
      $data[$gender . '_count']++;
    }
  }

  protected function handleStudentGrades(&$data, $student_uid, $subject_id, $grade_id, $gender) {
    if (!$grade_id) {
      $grade_id = self::NO_GRADE;
    }
    if (empty($data[$student_uid])) {

      $grade_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($grade_id);
      $data[$student_uid] = [
        'grades' => [],
        'grade_system' => $grade_term->bundle(),
        'merit' => 0,
        'eligibility' => FALSE,
        'gender' => $gender,
      ];
    }
    $data[$student_uid]['grades'][$subject_id] = $grade_id;
  }

  protected function handleSubjectGrades(&$data, $gender, $subject_id, $grade_id) {
    if (!$grade_id) {
      $grade_id = self::NO_GRADE;
    }
    if (!$grade_id) {
      return;
    }
    if (empty($data[$subject_id])) {
      $data[$subject_id] = [
        'male' => [
          'grades' => [],
        ],
        'female' => [
          'grades' => [],
        ],
        'all' => [
          'grades' => [],
        ],
      ];
    }
    if (empty($data[$subject_id]['all']['grades'][$grade_id])) {
      $data[$subject_id]['all']['grades'][$grade_id] = 0;
    }
    $data[$subject_id]['all']['grades'][$grade_id]++;

    if ($gender) {
      if (empty($data[$subject_id][$gender]['grades'][$grade_id])) {
        $data[$subject_id][$gender]['grades'][$grade_id] = 0;
      }
      $data[$subject_id][$gender]['grades'][$grade_id]++;
    }

  }

  protected function calculateMeritAndEligibility(array &$calculated_value) {
    foreach ($calculated_value['student_groups'] as $student_group_nid => &$item) {
      $total_merit = [];
      $total_eligibility = [];

      $type_check = ['all', 'male', 'female'];
      foreach ($type_check as $type) {
        $total_merit[$type] = 0;
        $total_eligibility[$type] = 0;
      }
      foreach ($item['students'] as &$student_data) {
        $gender = $student_data['gender'] ?? NULL;
        $this->calculateStudentMeritAndEligibility($student_data);
        $total_merit['all'] += $student_data['merit'];
        if ($gender && in_array($gender, $type_check)) {
          $total_merit[$gender] += $student_data['merit'];
        }
        if ($student_data['eligibility']) {
          $total_eligibility['all']++;
          if ($gender && in_array($gender, $type_check)) {
            $total_eligibility[$gender]++;
          }
        }
      }

      // Store total values for mean calculations in consumer.
      foreach ($type_check as $type) {
        $item['total_merit'][$type] = $total_merit[$type];
        $item['total_eligibility'][$type] = $total_eligibility[$type];
      }
    }
  }

  protected function prepareMaps() {
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

    if (!is_array($this->mandatorySubjects)) {
      $ids = $termStorage
        ->getQuery()
        ->condition('vid', 'school_subject')
        ->condition('field_school_type_versioned', SchoolTypeHelper::getSchoolTypeVersions('GR'), 'IN')
        ->condition('field_mandatory', TRUE)
        ->accessCheck(FALSE)
        ->execute();
      $mandatory_subjects = [];
      foreach ($ids as $id) {
        $mandatory_subjects[$id] = $id;
      }
      $this->mandatorySubjects = $mandatory_subjects;
    }

    if (!is_array($this->meritMap)) {
      $ids = $termStorage
        ->getQuery()
        ->condition('vid', ['af_grade_system', 'geg_grade_system'], 'IN')
        ->accessCheck(FALSE)
        ->execute();

      $merit_map = [];
      if (!empty($ids)) {
        $grade_terms = $termStorage->loadMultiple($ids);
        /** @var \Drupal\taxonomy\TermInterface $grade_term */
        foreach ($grade_terms as $grade_term) {
          $merit_map[$grade_term->id()] = $grade_term->get('field_merit')->value ?? 0;
        }
      }

      $this->meritMap = $merit_map;
    }

    if (!is_array($this->codeMap)) {
      $code_map = [];
      $results = $this->connection->select('taxonomy_term__field_subject_code_new', 'c')
        ->fields('c', ['entity_id', 'field_subject_code_new_value'])
        ->execute();

      foreach ($results as $result) {
        $code_map[$result->entity_id] = $result->field_subject_code_new_value;
      }

      $this->codeMap = $code_map;
    }
  }

  protected function calculateStudentMeritAndEligibility(array &$data) {
    $extra_meritable = [
      'M2' => TRUE,
    ];

    $merits = [];
    $extra_merits = [];
    $mandatory_ok = [];
    foreach ($this->mandatorySubjects as $subject_id) {
      $code = $this->codeMap[$subject_id];
      if ($code) {
        $mandatory_ok[$code] = FALSE;
      }

    }

    $alternative_mandatory = [
      'SV' => 'SVA',
      'SVA' => 'SV',
    ];

    $ok_subjects_count = 0;

    foreach ($data['grades'] as $subject_id => $grade_id) {
      if (empty($this->codeMap[$subject_id])) {
        continue;
      }

      $code = $this->codeMap[$subject_id];

      $merit_value = $this->meritMap[$grade_id] ?? 0;
      if (isset($this->mandatorySubjects[$subject_id]) && $merit_value > 0) {
        $mandatory_ok[$code] = TRUE;
      }

      if (isset($alternative_mandatory[$code]) && $merit_value > 0) {
        $mandatory_ok[$alternative_mandatory[$code]] = TRUE;
      }

      if ($merit_value > 0) {
        $ok_subjects_count++;
      }

      if (!empty($extra_meritable[$code])) {
        $extra_merits[$code] = $merit_value;
      }
      else {
        $merits[$code] = $merit_value;
      }
    }

    // Resolve eligibility.
    // eligibility ok if ok in all mandatory subject and at least 8 in total.
    // NOTE: eligibility is not used anymore at the moment. But kept for
    // potential future use, this rule needs to be updated if used again.
    $eligibility = TRUE;
    foreach ($mandatory_ok as $status) {
      if (!$status) {
        $eligibility = FALSE;
        break;
      }
    }
    $data['eligibility'] = $eligibility && $ok_subjects_count >= 8;

    // Resolve merit.
    // Merit is the sum of the 16 best merit values + 1 best in extra meritable.
    $merit = 0;
    $final_merits = array_values($merits);
    rsort($final_merits);
    $final_merits = array_slice($final_merits, 0, 16);

    if (!empty($extra_merits)) {
      $extra_merits = array_values($extra_merits);
      rsort($extra_merits);
      $extra_merits = array_slice($extra_merits, 0, 1);
      $final_merits[] = $extra_merits[0];
    }

    $data['merit'] = array_sum($final_merits);
  }

  public function getStudentGradeStatistics(string $grade_round_nid, int $student_uid) : array {
    $return = [
      'grades' => [],
      'merit' => 0,
      'grade_system' => NULL,
      'eligibility' => FALSE,
    ];

    $data = $this->getGradeStatistics($grade_round_nid);
    if (!empty($data['student_groups'])) {
      foreach ($data['student_groups'] as $student_group_nid => $stats) {
        if (!empty($stats['students'][$student_uid])) {
          return $stats['students'][$student_uid];
        }
      }
    }

    return $return;
  }

}
