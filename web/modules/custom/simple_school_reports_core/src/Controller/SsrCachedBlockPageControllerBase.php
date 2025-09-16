<?php

namespace Drupal\simple_school_reports_core\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for a cached page.
 */
abstract class SsrCachedBlockPageControllerBase extends SsrCachedPageControllerBase {

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected BlockManagerInterface $blockManager;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->blockManager = $container->get('plugin.manager.block');
    return $instance;
  }

  /**
   * @return \Drupal\simple_school_reports_grade_support\Utilities\SsrCachedBlockSettings[]
   */
  abstract public function getBlockSettings(): array;

  protected function useBlockDivider(): bool {
    return TRUE;
  }

  /**
   * @return \Drupal\Core\Block\BlockBase[]
   */
  public function getBlocks(): array {
    $args = func_get_args();
    $blocks = [];

    foreach ($this->getBlockSettings() as $block_settings) {
      if ($block_settings->type === 'block') {
        try {
          $block = $this->blockManager->createInstance($block_settings->id);
          if (!$block instanceof BlockBase) {
            continue;
          }
          $block->setConfigurationValue('block_settings', $block_settings);
          $blocks[$block_settings->id] = $block;
        }
        catch (\Exception $e) {
          continue;
        }

      }
    }
    return $blocks;
  }

  public function buildPageContent(): array {
    $args = func_get_args();

    $blocks = $this->getBlocks(...$args);
    $account = $this->currentUser();

    $build = [];
    $last_rendered_id = NULL;
    foreach ($blocks as $block_id => $block) {
      if (!$block->blockAccess($account, ...$args)->isAllowed()) {
        continue;
      }
      $block_render = $block->build();
      if (empty(Element::getVisibleChildren($block_render))) {
        continue;
      }

      /** @var \Drupal\simple_school_reports_grade_support\Utilities\SsrCachedBlockSettings $block_settings */
      $block_settings = $block->getConfiguration()['block_settings'];

      $build[$block_id] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'ssr-cached-block-page-content-block',
            'ssr-cached-block-page-content-block--' . $block_id,
          ],
        ],
      ];

      if ($block_settings?->label) {
        $build[$block_id]['label'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $block_settings->label,
        ];
      }
      $build[$block_id]['build'] = $block_render;
      if ($this->useBlockDivider()) {
        $build[$block_id]['divider'] = [
          '#type' => 'html_tag',
          '#tag' => 'hr',
        ];
      }
      $last_rendered_id = $block_id;
    }

    if ($last_rendered_id) {
      unset($build[$last_rendered_id]['divider']);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata(): CacheableMetadata {
    $cache = parent::getCacheableMetadata();
    $params = $this->routeMatch->getParameters();
    $blocks = $this->getBlocks($params);

    foreach ($blocks as $block) {
      if ($block instanceof CacheableDependencyInterface) {
        $cache->addCacheableDependency($block);
      }
    }

    return $cache;
  }

  public function access(AccountInterface $account = NULL): AccessResultInterface {
    if (!$account) {
      $account = $this->currentUser();
    }

    $args = func_get_args();
    unset($args[0]);

    $blocks = $this->getBlocks(...$args);

    if (empty($blocks)) {
      $block_access = AccessResult::forbidden();
    }
    else {
      $block_access = AccessResult::allowed();
      foreach ($blocks as $block) {
        $block_access = $block_access->andIf($block->blockAccess($account, ...$args));
      }
    }
    $access = parent::access();
    return $access->andIf($block_access);
  }

}
