<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a child care schema entity type.
 */
interface ChildCareSchemaInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  const SCHEMA_TYPE_CHILD_CARE = 'child_care';
  const SCHEMA_TYPE_STUDENT = 'student';

  public function isFuture(): bool;

  public function isActive(): bool;

  public function isEditable(): bool;

}
