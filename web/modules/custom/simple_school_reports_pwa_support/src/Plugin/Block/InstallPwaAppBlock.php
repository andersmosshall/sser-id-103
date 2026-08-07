<?php

namespace Drupal\simple_school_reports_pwa_support\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an install pwa app block.
 *
 * @Block(
 *  id = "ssr_pwa_install",
 *  admin_label = @Translation("SSR pwa install"),
 * )
 */
class InstallPwaAppBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

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
    protected ModuleHandlerInterface $moduleHandler,
    protected RequestStack $requestStack,
    protected BlockManagerInterface $blockManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('request_stack'),
      $container->get('plugin.manager.block')
    );
  }

  protected function isIos(): bool {
    $request = $this->requestStack->getCurrentRequest();
    $user_agent = $request->headers->get('User-Agent', '');
    return (bool) preg_match('/(iPhone|iPod|iPad)/i', $user_agent);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user_agent:ios']);
    $build = [];

    // Render ordinary pwa block if not on iOS.
    if (!$this->isIos()) {
      $block_id = 'pwa_add_to_home_screen';
      try {
        $block = $this->blockManager->createInstance($block_id);
        if ($block instanceof BlockBase) {
          $block->setConfiguration([
            'button_text' => 'Install app',
            'intro_text' => [
              'value' => '',
              'format' => 'plain_text',
            ],
          ]);
          $build['block'] = $block->build();
        }
      } catch (\Exception $e) {
        // Ignore.
        \Drupal::logger('simple_school_reports_pwa_support')->error('Error creating block instance: @error', ['@error' => $e->getMessage()]);
      }
    }
    // Render link to instructions for IOS.
    else {

      $build['link_wrapper'] = [
        '#type' => 'container',
      ];
      $url = Url::fromUri('https://support.apple.com/sv-se/guide/iphone/iphea86e5236/ios', [
        'absolute' => TRUE,
      ]);
      $build['link_wrapper']['link'] = [
        '#type' => 'link',
        '#attributes' => [
          'class' => ['button', 'button--primary'],
          // Open in new tab.
          'target' => '_blank',
        ],
        '#title' => $this->t('Install app'),
        '#url' => $url,
      ];
    }

    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(['user_agent:ios'], parent::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if (!$this->moduleHandler->moduleExists('simple_school_reports_pwa')) {
      return AccessResult::forbidden();
    }

    return parent::blockAccess($account);
  }

}
