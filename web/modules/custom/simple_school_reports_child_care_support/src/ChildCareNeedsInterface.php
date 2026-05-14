<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a child care needs entity type.
 */
interface ChildCareNeedsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
