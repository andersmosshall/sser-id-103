<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an assessment group user entity type.
 */
interface AssessmentGroupUserInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
