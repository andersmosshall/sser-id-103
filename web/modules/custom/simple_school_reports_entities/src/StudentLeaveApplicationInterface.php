<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a student leave application entity type.
 */
interface StudentLeaveApplicationInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * @return array
   *   An array of validation errors. Keyed by field name or 'general'.
   */
  public function validateApplication(): array;

}
