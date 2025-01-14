<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a dnp provisioning test settings entity type.
 */
interface DnpProvTestSettingsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
