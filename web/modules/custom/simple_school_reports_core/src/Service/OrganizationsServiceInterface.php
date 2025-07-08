<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\simple_school_reports_entities\SSROrganizationInterface;

/**
 * Interface describing the OrganizationsService.
 */
interface OrganizationsServiceInterface {

  public function getOrganization(string $organization_type): ?SSROrganizationInterface;

  public function assertOrganizations(): bool;
}
