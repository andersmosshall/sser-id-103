<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_school_reports_core\Service\UserMetaDataServiceInterface;
use Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface;
use Drupal\simple_school_reports_grade_support\GradeInterface;
use Drupal\simple_school_reports_grade_support\Utilities\GradeInfo;
use Drupal\simple_school_reports_grade_support\Utilities\GradeReference;

/**
 * Provides a service for managing grades.
 */
class GradeService implements GradeServiceInterface {

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
  public function getGradeReferences(array $student_ids, ?array $syllabus_ids = NULL): array {
    if (empty($student_ids)) {
      return [];
    }

    if (!empty($syllabus_ids)) {
      $syllabus_ids = $this->syllabusService->getSyllabusAssociations($syllabus_ids);
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
    $query->fields('gr', ['id', 'revision_id', 'grade', 'main_grader', 'registered', 'exclude_reason', 'trial', 'remark']);

    $results = $query->execute();

    $grades = [];

    $syllabus_ids = [];

    foreach ($results as $result) {
      $syllabus_ids[$result->syllabus] = $result->syllabus;

      $date = NULL;
      if ($result->registered) {
        $date = new \DateTime();
        $date->setTimestamp($result->registered);
      }

      $grades[$result->student][$result->syllabus][$result->revision_id] = new GradeInfo(
        $result->id,
        $result->revision_id,
        $result->student,
        $result->syllabus,
        $result->grade ?? NULL,
        $result->main_grader ?? NULL,
        $joint_graders_map[$result->revision_id] ?? [],
        $date,
        !empty($result->trial),
        $result->exclude_reason ?? NULL,
        $result->remark ?? NULL,
        false,
      );
    }

    $syllabus_weight = $this->syllabusService->getSyllabusWeight(array_values($syllabus_ids));;

    // Sort by user weight.
    uasort($grades, function ($a, $b) use ($student_weight) {
      return $student_weight[$a[0]] - $student_weight[$b[0]];
    });

    // Sort by syllabus weight.
    foreach ($grades as $student_id => $student_grades) {
      uasort($student_grades, function ($a, $b) use ($syllabus_weight) {
        return $syllabus_weight[$a[1]] - $syllabus_weight[$b[1]];
      });
    };

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
  public function getGradeLabel(int $tid): ?string {
    $cid = 'grade_terms_map';
    if (!isset($this->lookup[$cid])) {
      $map = [];
      $vids = array_keys(simple_school_reports_entities_grade_vid_options());
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => $vids]);
      foreach ($terms as $term) {
        $map[$term->id()] = $term;
      }
      $this->lookup[$cid] = $map;
    }
    /** @var \Drupal\taxonomy\TermInterface[] $map */
    $map = $this->lookup[$cid];

    return $map[$tid]?->label() ?? NULL;

  }
}
