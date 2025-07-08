<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_api_support\Plugin\rest\resource;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\simple_school_reports_api_support\SsrApiObjectNormalizer;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint;
use Drupal\simple_school_reports_api_support\DummyEndpointModel;
use Drupal\simple_school_reports_api_support\Service\SsrApiObjectsServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class SsrApiBase extends ResourceBase {

  protected SsrApiObjectNormalizer $serializer;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected RequestStack $requestStack,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cache,
    protected UuidInterface $uuid,
    protected SsrApiObjectsServiceInterface $apiObjectsService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->serializer = new SsrApiObjectNormalizer();
    $this->serializer->setNormalizer($this->serializer);
    $this->serializer->setDenormalizer($this->serializer);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('cache.default'),
      $container->get('uuid'),
      $container->get('simple_school_reports_api_support.api_objects'),
    );
  }

  protected function getStorage(): EntityStorageInterface {
    throw new NotFoundHttpException();
  }

  protected function getQuery($query_parameters = []): QueryInterface {
    return $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->range($query_parameters['range_start'], $query_parameters['range_limit']);
  }

  protected function executeQuery(QueryInterface $query): array {
    // Add default fallback sort by.
    $query->sort('created', 'DESC');

    // Todo Make pagination stuff.
    $cound_query = clone $query;

    $ids = $query->execute();
    $page_token = NULL;
    return [
      $ids,
      $page_token,
    ];
  }

  protected function normalizeObject(mixed $data): ?array {
    $normalized = $this->serializer->normalize($data, 'array', []);
    if (!is_array($normalized)) {
      return NULL;
    }
    return $normalized;
  }

  protected function getCurrentRequest(): ?Request {
    return $this->requestStack->getCurrentRequest();
  }

  protected function getEndpointModelGet(array $query_parameters = []): BaseEndpoint {
    return new DummyEndpointModel($query_parameters);
  }

  protected function getEndpointModelPost(array $query_parameters = []): BaseEndpoint {
    return new DummyEndpointModel($query_parameters);
  }

  protected function assertQueryParameters(string $method): array {
    $query_parameters = $this->getCurrentRequest()?->query->getIterator()->getArrayCopy() ?? [];
    $query_string = '';

    // ToDo handle pagination param...

    if ($method === 'GET') {
      try {
        $endpoint_model = $this->getEndpointModelGet($query_parameters);
//        $query_string = $endpoint_model->getQueryString();
      }
      catch (\Exception $e) {
        throw new BadRequestHttpException();
      }
    }

    if ($method === 'POST') {
      try {
        $endpoint_model = $this->getEndpointModelPost($query_parameters);
//        $query_string = $endpoint_model->getQueryString();
      }
      catch (\Exception $e) {
        throw new BadRequestHttpException();
      }
    }

    // Convert query string to array.
    $normalized_query_parameters = [];

    // Add range stuff.
    $normalized_query_parameters['range_start'] = $query_parameters['range_start'] ?? 0;
    $normalized_query_parameters['range_limit'] = $query_parameters['range_limit'] ?? 50;

    return $normalized_query_parameters;
  }

  protected function makeOkResponse(mixed $data, mixed $default = NULL): ModifiedResourceResponse {
    $response = new ModifiedResourceResponse(!empty($data) ? $this->normalizeObject($data) : $default);
    $response->setMaxAge(0);
    return $response;
  }

}
