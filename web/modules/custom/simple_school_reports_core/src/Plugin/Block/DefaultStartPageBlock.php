<?php

namespace Drupal\simple_school_reports_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\simple_school_reports_core\Service\StartPageContentServiceInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'Feedback form' block.
 *
 * @Block(
 *  id = "default_start_page_block",
 *  admin_label = @Translation("Default start page block"),
 * )
 */
class DefaultStartPageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\simple_school_reports_core\Service\StartPageContentServiceInterface
   */
  protected $startPageContentService;


  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    StartPageContentServiceInterface $start_page_content_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->startPageContentService = $start_page_content_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_school_reports_core.start_page_content_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['start_page_settings']);
    $cache->addCacheContexts(['user']);

    $build = [];

    $content = $this->startPageContentService->getFormattedStartPageContent('default');

    if ($content) {
      $build['startpage_info'] = [
        '#markup' => $content,
      ];
    }
    else {
      $build['startpage_info'] = [
        '#markup' => '<h2>Välkommen!</h2>',
      ];
    }

    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermissions($account, ['school staff permissions', 'administer budget', 'budget review'], 'OR');
  }

}
