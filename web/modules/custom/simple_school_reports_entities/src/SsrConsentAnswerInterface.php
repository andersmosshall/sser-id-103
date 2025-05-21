<?php

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a consent answer entity type.
 */
interface SsrConsentAnswerInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  const CONSENT_ANSWER_ACCEPTED = 1;
  const CONSENT_ANSWER_REJECTED = 0;

}
