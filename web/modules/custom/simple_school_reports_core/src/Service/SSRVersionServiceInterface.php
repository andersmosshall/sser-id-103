<?php

namespace Drupal\simple_school_reports_core\Service;

/**
 * Provides an interface defining SSRVersionService.
 */
interface SSRVersionServiceInterface {

  /**
   * @return string
   */
  public function getSsrVersion(): string;

}
