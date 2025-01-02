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
 *  id = "student_grade_statistics_proxy_block",
 *  admin_label = @Translation("Student grade statistics proxy block"),
 * )
 */
class GradeStatisticsProxyBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    ModuleHandlerInterface $module_handler,
    BlockManagerInterface $block_manager,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->blockManager = $block_manager;
    $this->currentUser = $current_user;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route']);
    $build = [];

    $block = $this->blockManager->createInstance('student_grade_statistics_block');
    if ($block instanceof StudentGradeStatisticsBlock && $block->access($this->currentUser)) {
      $build['block'] = $block->build();
    }

    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if (!$this->moduleHandler->moduleExists('simple_school_reports_grade_stats')) {
      return AccessResult::forbidden();
    }

    return parent::blockAccess($account);
  }

}
