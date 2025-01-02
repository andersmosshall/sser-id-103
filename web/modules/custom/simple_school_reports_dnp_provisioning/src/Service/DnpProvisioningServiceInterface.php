<?php

namespace Drupal\simple_school_reports_dnp_provisioning\Service;

use Drupal\simple_school_reports_dnp_support\DnpProvSettingsInterface;

/**
 * Provides an interface defining DnpProvisioningService.
 */
interface DnpProvisioningServiceInterface {

  /**
   * @return \Drupal\simple_school_reports_dnp_support\DnpProvSettingsInterface|null
   */
  public function getDnpProvisioningSettings(): ?DnpProvSettingsInterface;

}
