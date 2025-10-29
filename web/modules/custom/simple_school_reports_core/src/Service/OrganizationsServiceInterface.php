<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\simple_school_reports_entities\SSROrganizationInterface;

/**
 * Interface describing the OrganizationsService.
 */
interface OrganizationsServiceInterface {

  public function getOrganization(string $organization_type, string $school_type): ?SSROrganizationInterface;

  public function getSchoolUnitCode(string $school_type): ?string;
}
