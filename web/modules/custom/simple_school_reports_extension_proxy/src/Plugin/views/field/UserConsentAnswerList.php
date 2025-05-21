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
 * Field handler to consent answers for a given consent and target uid.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_consent_answer_list")
 */
class UserConsentAnswerList extends FieldPluginBase {

  use RedirectDestinationTrait;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface
   */
  protected $consentService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->routeMatch = $container->get('current_route_match');

    if ($instance->moduleHandler->moduleExists('simple_school_reports_consents')) {
      $instance->consentService = $container->get('simple_school_reports_consents.consent_service');
    }

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
      $cache->addCacheTags(['ssr_consent_answer_list:' . $consent_nid . ':' . $target_uid]);
      $cache->addCacheTags(['user_list:roles', 'user_list:new']);

      $consent_status = $this->consentService->getConsentStatus($consent_nid, $target_uid);

      $items = [];
      foreach ($consent_status as $status_data) {
        $item = [
          '#markup' => $status_data['name'] . ': ' . $status_data['status'],
        ];
        $items[] = $item;
      }

      $build = [
        '#theme' => 'item_list',
        '#items' => $items,
        '#title' => NULL,
        '#list_type' => 'ul',
      ];
    }

    $cache->addCacheContexts(['route']);
    $cache->applyTo($build);
    return $build;
  }

}
