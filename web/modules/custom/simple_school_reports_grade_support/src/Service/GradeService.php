<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface;
use Drupal\simple_school_reports_grade_support\GradeInterface;
use Drupal\simple_school_reports_grade_support\Utilities\GradeInfo;
use Drupal\simple_school_reports_grade_support\Utilities\GradeInfoMinimal;
use Drupal\simple_school_reports_grade_support\Utilities\GradeReference;

/**
 * Provides a service for managing grades.
 */
class GradeService implements GradeServiceInterface {

  use StringTranslationTrait;

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountInterface $currentUser,
    protected SyllabusServiceInterface $syllabusService,
    protected UserMetaDataServiceInterface $userMetaDataService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getStudentIdsWithGrades(?array $syllabus_ids, bool $only_active = TRUE): array {
    if (!empty($syllabus_ids)) {
      $syllabus_ids = $this->syllabusService->getSyllabusAssociations($syllabus_ids);
    }

    $query = $this->connection->select('ssr_grade', 'g');
    $query->innerJoin('users_field_data', 'u', 'u.uid = g.student');
    $query->innerJoin('user__field_first_name', 'fn', 'fn.entity_id = u.uid');
    $query->innerJoin('user__field_last_name', 'ln', 'ln.entity_id = u.uid');
    $query->leftJoin('user__field_grade', 'ug', 'ug.entity_id = u.uid');

    if (!empty($syllabus_ids)) {
      $query->condition('g.syllabus', $syllabus_ids, 'IN');
    }

    $results = $query
      ->fields('u', ['uid'])
      ->orderBy('ug.field_grade_value')
      ->orderBy('fn.field_first_name_value')
      ->orderBy('ln.field_last_name_value')
      ->execute();
    $student_ids = [];
    foreach ($results as $result) {
      $student_ids[] = $result->uid;
    }
    return $student_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getGradeReferences(array $student_ids, ?array $syllabus_ids = NULL): array {
    if (empty($student_ids)) {
      return [];
    }

    if (!empty($syllabus_ids)) {
      $syllabus_ids = $this->syllabusService->getSyllabusAssociations($syllabus_ids);
    }

    if (is_array($syllabus_ids) && count($syllabus_ids) === 0) {
      return [];
    }

    $query = $this->connection->select('ssr_grade', 'g')
      ->condition('g.student', $student_ids, 'IN')
      ->fields('g', ['id', 'revision_id'])
      ->orderBy('g.revision_id', 'ASC');

    if (!empty($syllabus_ids)) {
      $query->condition('g.syllabus', $syllabus_ids, 'IN');
    }

    $results = $query->execute();
    $grade_references = [];
    foreach ($results as $result) {
      $grade_references[$result->revision_id] = new GradeReference($result->id, $result->revision_id);
    }
    return $grade_references;
  }

  public function getGradeReferencesByRegistrationDate(\DateTime $from, \DateTime $to, array $student_ids, ?array $syllabus_ids = NULL) {
    if (empty($student_ids)) {
      return [];
    }

    if (!empty($syllabus_ids)) {
      $syllabus_ids = $this->syllabusService->getSyllabusAssociations($syllabus_ids);
    }

    // TODO correct fetch from correct table.
    $query = $this->connection->select('ssr_grade_revision', 'gr');
    $query->innerJoin('ssr_grade', 'g', 'g.id = gr.id');
    $query->condition('g.student', $student_ids, 'IN')
      ->condition('gr.registered', $from->getTimestamp(), '>=')
      ->condition('gr.registered', $to->getTimestamp(), '<=')
      ->fields('gr', ['id', 'revision_id'])
      ->orderBy('gr.revision_id', 'ASC');

    if (!empty($syllabus_ids)) {
      $query->condition('g.syllabus', $syllabus_ids, 'IN');
    }

    $results = $query->execute();
    $grade_references = [];
    foreach ($results as $result) {
      $grade_references[$result->revision_id] = new GradeReference($result->id, $result->revision_id);
    }
    return $grade_references;
  }

  /**
   * {@inheritdoc}
   */
  public function parseGradesFromReferences(array $grade_references): array {
    /** @var GradeReference[] $grade_references */
    $revision_ids = array_map(fn($grade_reference) => $grade_reference->revisionId, $grade_references);

    if (empty($revision_ids)) {
      return [];
    }

    $student_weight = $this->userMetaDataService->getUserWeights(FALSE);

    $joint_graders_map = [];
    $query = $this->connection->select('ssr_grade_revision__joint_grading_by', 'jg');
    $query->condition('jg.revision_id', $revision_ids, 'IN');
    $query->fields('jg', ['revision_id', 'joint_grading_by_target_id']);
    $results = $query->execute();

    foreach ($results as $result) {
      $joint_graders_map[$result->revision_id][] = $result->joint_grading_by_target_id;
    }

    $query = $this->connection->select('ssr_grade_revision', 'gr');
    $query->innerJoin('ssr_grade', 'g', 'g.id = gr.id');
    $query->condition('gr.revision_id', $revision_ids, 'IN');
    $query->fields('g', ['student', 'syllabus']);
    $query->fields('gr', [
      'id',
      'revision_id',
      'grade',
      'main_grader',
      'registered',
      'exclude_reason',
      'trial',
      'remark',
      'course',
    ]);
    $query->orderBy('gr.revision_id', 'ASC');

    $results = $query->execute();

    $has_previous_levels = FALSE;
    $grades = [];

    $syllabus_ids = [];

    foreach ($results as $result) {
      $syllabus_ids[$result->syllabus] = $result->syllabus;

      $date = NULL;
      if ($result->registered) {
        $date = new \DateTime();
        $date->setTimestamp($result->registered);
      }

      if (!$has_previous_levels && !empty($this->syllabusService->getSyllabusPreviousLevelIds($result->syllabus))) {
        $has_previous_levels = TRUE;
      }

      $grades[$result->student][$result->syllabus] = new GradeInfo(
        $result->id,
        $result->revision_id,
        $result->student,
        $result->syllabus,
        $result->course ?? NULL,
        $result->grade ?? NULL,
        $result->main_grader ?? NULL,
        $joint_graders_map[$result->revision_id] ?? [],
        $date,
        !empty($result->trial),
        $result->exclude_reason ?? NULL,
        $result->remark ?? NULL,
        $this->syllabusService->getSyllabusPoints($result->syllabus),
        FALSE,
      );
    }

    $syllabus_weight = $this->syllabusService->getSyllabusWeight(array_values($syllabus_ids));;

    // Sort by user weight.
    uasort($grades, function($a, $b) use ($student_weight) {
      $a_key1 = array_key_first($a);
      $b_key1 = array_key_first($b);

      if (!$a_key1 || !$b_key1) {
        return 0;
      }

      $student_id_a = $a[$a_key1]?->student ?? NULL;
      $student_id_b = $b[$b_key1]?->student ?? NULL;

      if (!$student_id_a || !$student_id_b) {
        return 0;
      }

      if (!isset($student_weight[$student_id_a]) || !isset($student_weight[$student_id_b])) {
        return 0;
      }

      return $student_weight[$student_id_a] <=> $student_weight[$student_id_b];
    });

    // Sort by syllabus weight.
    foreach ($grades as $student_id => &$student_grades) {
      uasort($student_grades, function($a, $b) use ($syllabus_weight) {
        $syllabus_id_a = $a->syllabusId ?? NULL;
        $syllabus_id_b = $b->syllabusId ?? NULL;

        if (!$syllabus_id_a || !$syllabus_id_b) {
          return 0;
        }

        if (!isset($syllabus_weight[$syllabus_id_a]) || !isset($syllabus_weight[$syllabus_id_b])) {
          return 0;
        }

        return $syllabus_weight[$syllabus_id_a] <=> $syllabus_weight[$syllabus_id_b];
      });
    };

    if ($has_previous_levels) {
      // Handle replaced grades.
      foreach ($grades as $student_id => $grades_data) {
        foreach ($grades_data as $syllabus_id => $grade_info) {
          $previous_level_ids = $this->syllabusService->getSyllabusPreviousLevelIds($syllabus_id);
          if (empty($previous_level_ids)) {
            continue;
          }

          // If passed, replace previous levels and set latest as previous level.
          if ($this->isPassed($grade_info)) {
            foreach ($previous_level_ids as $previous_level_id) {
              if (isset($grades[$student_id][$previous_level_id])) {
                $grades[$student_id][$previous_level_id]->replaced = TRUE;
              }
            }
          }
          // If not passed, replace current level if any previous is passed.
          else {
            $previous_level_passed = FALSE;
            foreach ($previous_level_ids as $previous_level_id) {
              if (isset($grades[$student_id][$previous_level_id])) {
                $previous_grade_info = $grades[$student_id][$previous_level_id];
                if ($this->isPassed($previous_grade_info)) {
                  $previous_level_passed = TRUE;
                  break;
                }
              }
            }
            if ($previous_level_passed) {
              $grades[$student_id][$syllabus_id]->replaced = TRUE;
            }
          }

          // Calculate gradeinfo minimal for last previous level.
          foreach (array_reverse($previous_level_ids) as $previous_level_id) {
            if (isset($grades[$student_id][$previous_level_id])) {
              /** @var \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $previous_grade_info */
              $previous_grade_info = $grades[$student_id][$previous_level_id];

              $grades[$student_id][$syllabus_id]->previousLevel = new GradeInfoMinimal(
                $previous_grade_info->id,
                $previous_grade_info->revisionId,
                $previous_grade_info->student,
                $previous_grade_info->syllabusId,
                $previous_grade_info->gradeTid,
                $previous_grade_info->points,
                $previous_grade_info->previousLevel,
              );
              break;
            }
          }

        }
      }
    }

    return $grades;
  }

  /**
   * {@inheritdoc}
   */
  public function parseGradesFromFilter(array $student_ids, ?array $syllabus_ids = NULL): array {
    $grade_references = $this->getGradeReferences($student_ids, $syllabus_ids);
    return $this->parseGradesFromReferences($grade_references);
  }

  /**
   * {@inheritdoc}
   */
  public function getGradeLabel(GradeInfo|GradeInfoMinimal $grade_info, ?array $exclude_label_map = []): ?string {
    if ($grade_info->gradeTid) {
      return $this->getGradeLabelFromTermId($grade_info->gradeTid);
    }

    if (!$grade_info->excludeReason) {
      return NULL;
    }

    // Set default exlude label map.
    $map = $exclude_label_map + [
        GradeInterface::EXCLUDE_REASON_ADAPTED_STUDIES => $this->t('No grade - adapted studies'),
      ];

    if (isset($map[$grade_info->excludeReason])) {
      return $map[$grade_info->excludeReason];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGradeLabelFromTermId(string|int $tid): ?string {
    $cid = 'grade_terms_map';
    if (!isset($this->lookup[$cid])) {
      $map = [];
      $vids = array_keys(simple_school_reports_entities_grade_vid_options());
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => $vids]);
      foreach ($terms as $term) {
        $map[$term->id()] = $term;
      }
      $this->lookup[$cid] = $map;
    }
    /** @var \Drupal\taxonomy\TermInterface[] $map */
    $map = $this->lookup[$cid];

    return $map[$tid]?->label() ?? NULL;
  }

  protected function getPassedTermIds(): array {
    $cid = 'grade_passed_term_ids';
    if (!isset($this->lookup[$cid])) {
      $passed_tids = [];
      $vids = array_keys(simple_school_reports_entities_grade_vid_options());
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => $vids]);
      foreach ($terms as $term) {
        $merit = $term->get('field_merit')->value;
        if ($merit >= 10) {
          $passed_tids[] = $term->id();
        }
      }
      $this->lookup[$cid] = $passed_tids;
    }
    return $this->lookup[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function getCourseCode(GradeInfo $grade_info): string {
    $syllabus_id = $grade_info->syllabusId;
    if (empty($syllabus_id)) {
      return '';
    }

    $map = $this->syllabusService->getSyllabusCourseCodesInOrder([$syllabus_id]);
    return $map[$syllabus_id] ?? '';
  }

  public function isPassed(GradeInfo|GradeInfoMinimal $grade_info): bool {
    $term_id = $grade_info->gradeTid ?? '*';
    return in_array($term_id, $this->getPassedTermIds());
  }

  public function hasGrade(GradeInfo|GradeInfoMinimal $grade_info): bool {
    return !empty($this->getGradeLabelFromTermId($grade_info->gradeTid ?? '*'));
  }

  /**
   * {@inheritdoc}
   */
  public function getSyllabusLabel(GradeInfo|GradeInfoMinimal $grade_info): string {
    $syllabus_id = $grade_info->syllabusId;
    if (empty($syllabus_id)) {
      return '';
    }

    $map = $this->syllabusService->getSyllabusLabelsInOrder([$syllabus_id]);
    return $map[$syllabus_id] ?? '';
  }

  public function getCodes(GradeInfo $grade_info): array {
    // For now only P (trial) can be resolved.
    if ($grade_info->trial) {
      return ['P'];
    }
    return [];
  }

  /**
   * @param \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $grade_info
   *
   * @return \Drupal\simple_school_reports_grade_support\Utilities\GradeInfoMinimal[]
   */
  protected function getPreviousLevelsInfo(GradeInfo $grade_info, bool $skip_if_replaced = TRUE, bool $skip_if_no_grade = TRUE): array {
    if ($skip_if_replaced && $grade_info->replaced) {
      return [];
    }

    if ($skip_if_no_grade && !$this->hasGrade($grade_info)) {
      return [];
    }

    $levels = [];
    $current_level = $grade_info->previousLevel ?? NULL;
    while ($current_level) {
      $levels[] = $current_level;
      $current_level = $current_level->previousLevel ?? NULL;
    }
    return array_reverse($levels);
  }

  public function getLevelsNumericalNames(GradeInfo $grade_info): array {
    $previous_levels = $this->getPreviousLevelsInfo($grade_info);
    $names = [];

    foreach ($previous_levels as $previous_level) {
      $syllabus_info = $this->syllabusService->getSyllabusInfo($previous_level->syllabusId);
      $names[] = $syllabus_info['level_numerical'] ?? '-';
    }

    return $names;
  }

  public function getLevelsGradeReferences(GradeInfo $grade_info): array {
    $previous_levels = $this->getPreviousLevelsInfo($grade_info);
    $grade_references = [];
    foreach ($previous_levels as $previous_level) {
      $grade_references[] = new GradeReference($previous_level->id, $previous_level->revisionId);
    }
    return $grade_references;
  }

  public function getAggregatedPoints(GradeInfo $grade_info): ?int {
    $previous_levels = $this->getPreviousLevelsInfo($grade_info);
    if (empty($previous_levels)) {
      return $grade_info->points;
    }
    $aggregated_points = $grade_info->points ?? 0;
    foreach ($previous_levels as $previous_level) {
      $aggregated_points += $previous_level->points ?? 0;
    }
    return $aggregated_points;
  }

}
