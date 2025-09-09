<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_grade_support\Entity\GradeSnapshotPeriod;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining GradeSnapshotService.
 */
interface GradeSnapshotServiceInterface {

  public function getSnapshotPeriodId(?\DateTime $date = NULL): int|string;

}
