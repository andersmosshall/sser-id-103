<?php

namespace Drupal\simple_school_reports_core\Plugin\Block;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'StatisticsBlockBase' block.
 */
abstract class StatisticsBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  protected $cacheObject;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\simple_school_reports_core\Service\TermServiceInterface $term_service
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    RequestStack $request_stack,
    RouteMatchInterface $route_match,
    UuidInterface $uuid,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
    $this->uuidService = $uuid;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('uuid'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('module_handler'),
    );
  }

  abstract public function getLibraries() : array;
  abstract public function getGraphData() : array;
  abstract public function getGraphDataType() : string;

  public function getTable() : array {
    return [];
  }

  protected function getCacheObject() {
    if (!$this->cacheObject) {
      $cache = new CacheableMetadata();
      $cache->addCacheContexts(['route', 'url.query_args']);
      $this->cacheObject = $cache;
    }
    return $this->cacheObject;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $chart_data = $this->getGraphData();

    if (!empty($chart_data)) {
      $uuid = 'id-' . $this->uuidService->generate();

      $build['#attached']['library'] = $this->getLibraries();
      $build['#attached']['drupalSettings']['ssrGraphData'][$this->getGraphDataType()][$uuid] = $chart_data;


      $build['graph_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['graph-wrapper'],
          'style' => ['height: 400px'],
        ],
      ];
      $build['graph_wrapper']['graph'] = [
        '#type' => 'html_tag',
        '#tag' => 'canvas',
        '#attributes' => [
          'id' => $uuid,
        ],
      ];
    }

    $build['table'] = $this->getTable();
    $cache = $this->getCacheObject();
    $cache->applyTo($build);
    return $build;
  }

  public function getCacheContexts() {
    return $this->getCacheObject()->getCacheContexts();
  }

  public function getCacheMaxAge() {
    return $this->getCacheObject()->getCacheMaxAge();
  }

  public function getCacheTags() {
    return $this->getCacheObject()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowed();
  }

}
