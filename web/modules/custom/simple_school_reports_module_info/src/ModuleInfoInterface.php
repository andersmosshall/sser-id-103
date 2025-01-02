<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_module_info;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a module info entity type.
 */
interface ModuleInfoInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
