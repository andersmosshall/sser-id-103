<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_maillog;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a mail count entity type.
 */
interface SsrMailCountInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
