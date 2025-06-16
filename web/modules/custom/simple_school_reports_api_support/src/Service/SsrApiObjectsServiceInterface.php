<?php

namespace Drupal\simple_school_reports_api_support\Service;

use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation;

/**
 * Provides an interface defining SsrApiObjectsService.
 */
interface SsrApiObjectsServiceInterface {

  const INVALID_OBJECT = 'invalid';

  public function makeOrganization(string $id): ?Organisation;

  /**
   * Make a list of organizations from an array of IDs.
   *
   * @param string[] $ids
   *   An array of organization IDs.
   *
   * @return Organisation[]
   *   An array of Organization objects.
   */
  public function makeOrganizations(array $ids): array;

}
