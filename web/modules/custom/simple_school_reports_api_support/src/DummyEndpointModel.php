<?php

namespace Drupal\simple_school_reports_api_support;

use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\EndpointTrait;

class DummyEndpointModel extends BaseEndpoint implements Endpoint {
  use EndpointTrait;

  public function __construct(array $queryParameters = []) {
    $this->queryParameters = $queryParameters;
  }

  public function getMethod(): string {
    return 'GET';
  }

  public function getUri(): string {
    return '/dummy';
  }

  public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = NULL): array {
    return [[], NULL];
  }

  public function getExtraHeaders(): array {
    return ['Accept' => ['application/json']];
  }

  protected function getQueryOptionsResolver(): \Symfony\Component\OptionsResolver\OptionsResolver {
    $optionsResolver = parent::getQueryOptionsResolver();
    $optionsResolver->setDefined([]);
    $optionsResolver->setRequired([]);
    $optionsResolver->setDefaults([]);
    return $optionsResolver;
  }

  /**
   * {@inheritdoc}
   *
   * @return null
   */
  protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = NULL) {
    return NULL;
  }

  public function getAuthenticationScopes(): array {
    return ['BearerAuth'];
  }

}
