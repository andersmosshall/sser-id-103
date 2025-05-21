<?php

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a signing entity type.
 */
interface SsrSigningInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  const SIGNING_TYPE_EMAIL = 'email';

}
