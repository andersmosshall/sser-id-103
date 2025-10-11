<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_grade_support\Entity\GradeSnapshotPeriod;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining GradeSnapshotService.
 */
interface GradeSnapshotServiceInterface {

  public function makeSnapshotIdentifier(int|string $snapshot_period_id, int|string $student_id): string;

  public function getSnapshotPeriodId(array $school_type_versions, ?\DateTime $date = NULL): int|string;

  public function makeSnapshot(int|string $student_id, array $school_type_versions): void;

  public function updateSnapshotsForGrade(int|string $old_grade_revision_id, int|string $new_grade_revision_id, string $student_id): void;

}
