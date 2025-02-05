<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_schema_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a ssr schema entry entity type.
 */
interface SSRSchemaEntryInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
