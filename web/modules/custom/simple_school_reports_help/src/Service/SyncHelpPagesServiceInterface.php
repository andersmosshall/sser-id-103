<?php

namespace Drupal\simple_school_reports_help\Service;

/**
 * Provides an interface defining ModuleInfoService.
 */
interface SyncHelpPagesServiceInterface {

  public function syncHelpPages(bool $force = FALSE): bool;

}
