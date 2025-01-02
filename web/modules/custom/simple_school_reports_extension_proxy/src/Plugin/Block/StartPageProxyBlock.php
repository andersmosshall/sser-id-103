<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_grade_stats\Plugin\Block\StudentGradeStatisticsBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a GradeStatisticsProxyBlock block.
 *
 * @Block(
 *  id = "start_page_proxy_block",
 *  admin_label = @Translation("Start page proxy block"),
 * )
 */
class StartPageProxyBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var RouteMatchInterface
   */
  protected $routeMatch;

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ModuleHandlerInterface $module_handler,
    BlockManagerInterface $block_manager,
    AccountProxyInterface $current_user,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->blockManager = $block_manager;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
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
      $container->get('plugin.manager.block'),
      $container->get('current_user'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user', 'route']);
    $cache->addCacheTags(['start_page_settings']);

    $build = [];

    $block_id = $this->getStartPageBlockId($this->routeMatch->getRouteName());
    if ($block_id) {
      try {
        $block = $this->blockManager->createInstance($block_id);
        if ($block instanceof BlockBase && $block->access($this->currentUser)) {
          $build['block'] = $block->build();
        }
      } catch (\Exception $e) {
        // Ignore.
      }
    }

    $cache->applyTo($build);
    return $build;
  }

  /**
   * @param string $route_name
   *
   * @return string
   */
  public function getStartPageBlockId(string $route_name): ?string {
    if ($route_name === 'simple_school_reports_core.start_page_default') {
      return 'default_start_page_block';
    }

    if ($route_name === 'simple_school_reports_caregiver_login.start_page_caregiver') {
      return 'caregiver_start_page_block';
    }

    return NULL;
  }

}
