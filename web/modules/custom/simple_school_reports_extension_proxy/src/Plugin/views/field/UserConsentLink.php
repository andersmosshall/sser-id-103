<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_extension_proxy\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show user consent link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_consent_link")
 */
class UserConsentLink extends FieldPluginBase {

  use RedirectDestinationTrait;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $target_uid = $values->uid ?? 0;
    if (!$target_uid || !$this->moduleHandler->moduleExists('simple_school_reports_consents')) {
      return '';
    }

    $build = [];
    $cache = new CacheableMetadata();

    $consent = $this->routeMatch->getParameter('node');
    if ($consent instanceof NodeInterface && $consent->bundle() === 'consent') {
      $consent_nid = $consent->id();
      $cache->addCacheTags(['node:' . $consent_nid]);
      $cache->addCacheTags(['consent_answer_list:' . $consent_nid . ':' . $target_uid]);

      if (!$consent->get('field_locked')->value) {
        $operations[] = [
          'title' => $this->t('Manage'),
          'weight' => 0,
          'url' => Url::fromRoute('simple_school_reports_consents.handle_consent', ['node' => $consent_nid, 'user' => $target_uid]),
          'query' => $this->getDestinationArray(),
        ];

        $build = [
          '#type' => 'operations',
          '#links' => $operations,
        ];
      }
    }

    $cache->addCacheContexts(['route']);
    $cache->applyTo($build);
    return $build;
  }

}
