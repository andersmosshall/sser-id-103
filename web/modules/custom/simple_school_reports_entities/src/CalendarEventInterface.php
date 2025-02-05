<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a calendar event entity type.
 */
interface CalendarEventInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
