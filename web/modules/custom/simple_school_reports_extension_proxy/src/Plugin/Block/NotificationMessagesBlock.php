<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_extension_proxy\Events\NotificationMessagesEvent;
use Drupal\simple_school_reports_extension_proxy\Events\SsrEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a 'NotificationMessagesBlock' block.
 *
 * @Block(
 *  id = "ssr_notifications_messages",
 *  admin_label = @Translation("Notifications messages"),
 * )
 */
class NotificationMessagesBlock extends BlockBase implements ContainerFactoryPluginInterface {


  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    protected AccountInterface $currentUser,
    protected CacheBackendInterface $cache,
    protected EventDispatcherInterface $dispatcher,
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
      $container->get('current_user'),
      $container->get('cache.default'),
      $container->get('event_dispatcher'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $event = new NotificationMessagesEvent(
      $this->currentUser,
      $this->cache,
    );
    $this->dispatcher->dispatch($event, SsrEvents::NOTIFICATION_MESSAGES);

    $warning_messages = $event->getInformationMessages();
    $error_messages = $event->getImportantMessages();

    if (!empty($warning_messages)) {
      $messages['warning'] = $warning_messages;
    }

    if (!empty($error_messages)) {
      $messages['error'] = $error_messages;
    }

    if (!empty($messages)) {
      $build['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => $this->t('Information'),
          'error' => $this->t('Important'),
          'warning' => $this->t('Information'),
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Disable cache.
    return 0;
  }

}
