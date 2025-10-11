<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_school_reports_core\SchoolTypeHelper;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_entities\Service\SyllabusServiceInterface;

/**
 * Provides a service for managing grade snapshots.
 */
class GradeSnapshotService implements GradeSnapshotServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TermServiceInterface $termService,
    protected GradeServiceInterface $gradeService,
    protected SyllabusServiceInterface $syllabusService,
  ) {}

  protected function normalizeSchoolTypeVersions(array $school_type_versions): array {
    if (empty($school_type_versions)) {
      throw new \InvalidArgumentException('school_type_versions must not be empty');
    }
    // GY versions are always associated.
    $gy_school_type_versions = SchoolTypeHelper::getSchoolTypeVersions('GY');
    // Make sure all gy versions are included if any of them are included.
    if (count(array_intersect($gy_school_type_versions, $school_type_versions)) > 0) {
      $school_type_versions = array_merge($school_type_versions, $gy_school_type_versions);
    }
    return array_unique($school_type_versions);
  }

  /**
   * {@inheritdoc}
   */
  public function getSnapshotPeriodId(array $school_type_versions, ?\DateTime $date = NULL): int|string {
    $school_type_versions = $this->normalizeSchoolTypeVersions($school_type_versions);

    if (!$date) {
      $date = new \DateTime('now');
    }
    $term_index = $this->termService->getDefaultTermIndex($date);
    $cid = 'snapshot_id:' . $term_index;
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }

    $snapshot_period_storage = $this->entityTypeManager->getStorage('ssr_grade_snapshot_period');

    $id = current($snapshot_period_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('period_index', $term_index)
      ->condition('school_type_versioned', $school_type_versions, 'IN')
      ->execute());

    if (!$id) {
      $parsed_term = $this->termService->parseDefaultTermIndex($term_index);
      $label = $parsed_term['semester_name_short'];

      $snapshot_period = $snapshot_period_storage->create([
        'label' => $label,
        'period_index' => $term_index,
        'status' => 0,
        'langcode' => 'sv',
      ]);
      $snapshot_period->save();
      $id = $snapshot_period->id();
    }

    $this->lookup[$cid] = $id;
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function makeSnapshotIdentifier(int|string $snapshot_period_id, int|string $student_id): string {
    return 's.' . $student_id . '.p.' . $snapshot_period_id;
  }

  /**
   * {@inheritdoc}
   */
  public function makeSnapshot(int|string $student_id, array $school_type_versions): void {
    $school_type_versions = $this->normalizeSchoolTypeVersions($school_type_versions);

    $snapshot_period_id = $this->getSnapshotPeriodId($school_type_versions);

    $student = $this->entityTypeManager->getStorage('user')->load($student_id);
    if (!$student) {
      return;
    }

    $identifier = $this->makeSnapshotIdentifier($snapshot_period_id, $student_id);
    $snapshot_storage = $this->entityTypeManager->getStorage('ssr_grade_snapshot');
    /** @var \Drupal\simple_school_reports_grade_support\GradeSnapshotInterface|null $snapshot */
    $snapshot = current($snapshot_storage->loadByProperties(['identifier' => $identifier]));

    if (!$snapshot) {
      $snapshot = $snapshot_storage->create([
        'langcode' => 'sv',
      ]);
    }

    $current_revision_ids = array_column($snapshot->get('grades')->getValue(), 'revision_id');
    $current_revision_ids = array_unique($current_revision_ids);
    sort($current_revision_ids);

    $new_revision_ids = [];
    $new_snapshot_grades = [];

    $syllabus_ids = $this->syllabusService->getSyllabusIdsFromSchoolTypes($school_type_versions);

    $grade_references = $this->gradeService->getGradeReferences([$student_id], $syllabus_ids);

    foreach ($grade_references as $grade_reference) {
      $new_revision_ids[] = $grade_reference->revisionId;
      $new_snapshot_grades[] = [
        'target_revision_id' => $grade_reference->revisionId,
        'target_id' => $grade_reference->id,
      ];
    }
    $new_revision_ids = array_unique($new_revision_ids);
    sort($new_revision_ids);

    if ($new_revision_ids == $current_revision_ids) {
      return;
    }

    $snapshot->set('identifier', $identifier);
    $snapshot->set('label', $identifier);
    $snapshot->set('grades', $new_snapshot_grades);
    $snapshot->set('status', TRUE);
    $snapshot->set('grade_snapshot_period', ['target_id' => $snapshot_period_id]);
    $snapshot->set('student', ['target_id' => $student_id]);

    $snapshot->set('school_grade', $student->get('field_grade')->value);
    $snapshot->set('gender', $student->get('field_gender')->value);

    $snapshot->set('grades', $new_snapshot_grades);

    $violations = $snapshot->validate();
    if (count($violations) > 0) {
      throw new \RuntimeException('Failed to create grade snapshot for student ' . $student_id . ': ' . Json::encode($violations));
    }

    $snapshot->save();
  }

  /**
   * {@inheritdoc}
   */
  public function updateSnapshotsForGrade(int|string $old_grade_revision_id, int|string $new_grade_revision_id, string $student_id): void {
    if ((string) $old_grade_revision_id === (string) $new_grade_revision_id) {
      return;
    }
    $this->connection->update('ssr_grade_snapshot__grades')
      ->fields(['grades_target_revision_id' => $new_grade_revision_id])
      ->condition('grades_target_revision_id', $old_grade_revision_id)
      ->execute();
    Cache::invalidateTags(['ssr_grade_snapshot_list:student:' . $student_id]);
  }

}
