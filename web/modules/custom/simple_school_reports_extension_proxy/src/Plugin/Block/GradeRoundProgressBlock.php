<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a GradeRoundProgress block.
 *
 * @Block(
 *  id = "grade_round_progress_block",
 *  admin_label = @Translation("Grade round progress block"),
 * )
 */
class GradeRoundProgressBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route']);
    $build = [];

    $node = $this->routeMatch->getParameter('node');
    if (is_numeric($node)) {
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }
    $cache->addCacheableDependency($node);

    /** @var \Drupal\simple_school_reports_grade_registration\Service\GradeRoundProgressServiceInterface $progress_service */
    $progress_service = \Drupal::service('simple_school_reports_grade_registration.grade_round_progress_service');
    $value = $progress_service->getProgress($node->id());

    $build['label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Register grade for @name', ['@name' => $node->label()]),
    ];
    $build['progress'] = [
      '#theme' => 'progress_bar',
      '#label' => $this->t('Grade round progress'),
      '#percent' => $value,
    ];

    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if (!$this->moduleHandler->moduleExists('simple_school_reports_grade_registration')) {
      return AccessResult::forbidden();
    }

    if ($node = $this->routeMatch->getParameter('node')) {
      if (is_numeric($node)) {
        $node = $this->entityTypeManager->getStorage('node')->load($node);
      }

      if ($node instanceof NodeInterface && $node->bundle() === 'grade_round') {
        return AccessResult::allowed()->addCacheContexts(['route']);
      }
    }

    return AccessResult::forbidden()->addCacheContexts(['route']);
  }

}
