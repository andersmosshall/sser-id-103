<?php

namespace Drupal\simple_school_reports_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Link;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'Feedback form' block.
 *
 * @Block(
 *  id = "current_term_invalid_block",
 *  admin_label = @Translation("Current term invalid block"),
 * )
 */
class CurrentTermInvalidBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\simple_school_reports_core\Service\TermServiceInterface
   */
  protected $termService;

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
    TermServiceInterface $term_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->termService = $term_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simple_school_reports_core.term_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['taxonomy_term_list:term']);
    $cache->addCacheContexts(['user.permissions']);
    $cache->setCacheMaxAge(18000);

    $build = [];

    $now = time();
    $end = $this->termService->getCurrentTermEnd(FALSE);
    $link = Link::createFromRoute($this->t('here'),'view.terms.terms')->toString();
    $messages = [];
    if (!$end) {
      $messages['error'][] = $this->t('No term is active. Administer terms @link.', ['@link' => $link]);
    }
    else if ($now > $end) {
      $messages['error'][] = $this->t('The current term has ended. Administer terms @link.', ['@link' => $link]);
    }

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
    return AccessResult::allowedIfHasPermission($account,'administer simple school reports settings');
  }

}
