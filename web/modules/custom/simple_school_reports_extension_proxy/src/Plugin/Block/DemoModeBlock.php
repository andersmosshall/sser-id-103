<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'DemoModeBlock' block.
 *
 * @Block(
 *  id = "ssr_demo_mode_block",
 *  admin_label = @Translation("Demo mode active"),
 * )
 */
class DemoModeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    protected ModuleHandlerInterface $moduleHandler,
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $messages = [
      'warning' => [
        $this->t('Demo mode is active. No messages are sent to real email addresses. This is a demonstration site and data can be removed at any time. Do not use real personal information.'),
      ],
    ];

    $build['message'] = [
      '#theme' => 'status_messages',
      '#message_list' => $messages,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($this->moduleHandler->moduleExists('simple_school_reports_demo'));
  }

}
