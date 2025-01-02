<?php

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a meeting entity type.
 */
interface SsrMeetingInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  public function setKeepListCache(bool $value = TRUE): self;

}
