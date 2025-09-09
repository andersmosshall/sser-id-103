<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a course to grade entity type.
 */
interface GradeRegistrationCourseInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  public const REGISTRATION_STATUS_NOT_STARTED = 'ns';
  public const REGISTRATION_STATUS_STARTED = 'started';
  public const REGISTRATION_STATUS_DONE = 'done';

}
