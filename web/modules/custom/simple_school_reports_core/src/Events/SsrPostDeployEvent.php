<?php

namespace Drupal\simple_school_reports_core\Events;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered after the deployment of the Simple School Reports.
 */
class SsrPostDeployEvent extends Event {

  public function __construct() {}

}
