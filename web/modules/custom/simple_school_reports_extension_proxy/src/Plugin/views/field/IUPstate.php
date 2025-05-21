<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_extension_proxy\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show progress in grade round.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("iup_state")
 */
class IUPstate extends FieldPluginBase {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');
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
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['node_list:iup']);
    $uid = $values->uid ?? 0;
    $value = '';
    if ($uid && $this->moduleHandler->moduleExists('simple_school_reports_iup')) {
      $value = 'not_started';
      $iup_round_nid = $this->routeMatch->getRawParameter('node');
      if ($iup_round_nid) {
        $node_storage = $this->entityTypeManager
          ->getStorage('node');

        $iup_nid = current($node_storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', 'iup')
          ->condition('field_student', $uid)
          ->condition('field_iup_round', $iup_round_nid)
          ->execute()
        );
        if ($iup_nid && $iup_node = $node_storage->load($iup_nid)) {
          $cache = new CacheableMetadata();
          $cache->addCacheableDependency($iup_node);
          if ($iup_node->get('field_state')->value) {
            $value = $iup_node->get('field_state')->value;
          }
        }
      }
    }

    $build = [];
    $build['value'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['field-state--' . str_replace(' ', '-', mb_strtolower($value))],
      ],
      '#value' => t(ucfirst(str_replace('_', ' ', $value))),
    ];

    $cache->addCacheContexts(['route']);
    $cache->applyTo($build);
    return $build;
  }

}
