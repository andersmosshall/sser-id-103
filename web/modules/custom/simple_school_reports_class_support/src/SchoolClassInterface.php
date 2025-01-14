<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_class_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a school class entity type.
 */
interface SchoolClassInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
