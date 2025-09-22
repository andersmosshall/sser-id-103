<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a grade snapshot entity type.
 */
interface GradeSnapshotInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
