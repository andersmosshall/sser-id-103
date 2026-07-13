<?php

namespace Drupal\simple_school_reports_core\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * General hook implementations.
 */
class GeneralHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new GeneralHooks object.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected TimeInterface $time,
  ) {
  }

  // Preparation for future hooks.
}
