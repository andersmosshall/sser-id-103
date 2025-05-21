<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'ConsentUnhandledBlock' block.
 *
 * @Block(
 *  id = "consent_unhandled_block",
 *  admin_label = @Translation("Current user unhandled consents message"),
 * )
 */
class ConsentUnhandledBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  protected $currentUser;

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
    AccountInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
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
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user']);
    $cache->addCacheTags(['user_list', 'ssr_consent_answer_list', 'node_list:consent']);

    $build = [];

    $link = Link::createFromRoute($this->t('here'),'simple_school_reports_consents.user_consents_page', ['user' => $this->currentUser->id()])->toString();
    $messages = [];

    $messages['warning'][] = $this->t('You have consents to answer. You can handle your consents @link.', ['@link' => $link]);

    if (!empty($messages)) {
      $build['message'] = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];
    }

    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    if (!$this->moduleHandler->moduleExists('simple_school_reports_consents')) {
      return AccessResult::forbidden();
    }

    /** @var \Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface $consent_service */
    $consent_service = \Drupal::service('simple_school_reports_consents.consent_service');

    $has_unhandled_consents = !empty($consent_service->getUnHandledConsentIds($this->currentUser->id()));

    $access = AccessResult::allowedIf($has_unhandled_consents);
    $access->cachePerUser();
    $access->addCacheTags(['user_list', 'ssr_consent_answer_list', 'node_list:consent']);

    return $access;
  }

}
