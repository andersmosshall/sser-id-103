<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_api_support\Plugin\rest\resource;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetOrganisation;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostOrganisationsLookup;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Meta;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisations;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationsLookupPostRequest;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint;

/**
 * Implementation of organisation list api.
 *
 * @RestResource (
 *   id = "ssr_api_organization_list",
 *   label = @Translation("Organization list"),
 *   uri_paths = {
 *     "canonical" = "/api/v2.0/organisations",
 *     "create" = "/api/v2.0/organisations/lookup"
 *   }
 * )
 */
final class SsrApiOrganizationListResource extends SsrApiBase {

  protected function getEndpointModelGet(array $query_parameters = []): BaseEndpoint {
    return new GetOrganisation($query_parameters);
  }

  protected function getEndpointModelPost(array $query_parameters = []): BaseEndpoint {
    return new PostOrganisationsLookup(new OrganisationsLookupPostRequest(), $query_parameters);
  }

  protected function getStorage(): EntityStorageInterface {
    return $this->entityTypeManager->getStorage('ssr_organization');
  }

  /**
   * Responds to GET requests.
   */
  public function get(): ModifiedResourceResponse {
    $query_parameters = $this->assertQueryParameters('GET');
    $query = $this->getQuery($query_parameters);

    [$ids, $page_token] = $this->executeQuery($query);
    $organizations = $this->apiObjectsService->makeOrganizations($ids);

    $response = new Organisations();
    $response->setData($organizations);
    $response->setPageToken($page_token);

    return $this->makeOkResponse($response);
  }

  /**
   * Responds to POST requests.
   */
  public function post(array $data): ModifiedResourceResponse {
    $query_parameters = $this->assertQueryParameters('POST');
    $query = $this->getQuery($query_parameters);
    // ToDo handle filters and pagination.
    $ids = $query->execute();
    return $this->makeOkResponse([]);
  }
}
