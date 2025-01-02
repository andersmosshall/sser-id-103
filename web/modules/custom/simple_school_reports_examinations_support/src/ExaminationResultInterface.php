<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an examination result entity type.
 */
interface ExaminationResultInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
