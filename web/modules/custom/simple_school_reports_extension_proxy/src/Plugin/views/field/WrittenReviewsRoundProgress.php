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
 * Field handler to show progress in written reviews round.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("written_reviews_round_progress")
 */
class WrittenReviewsRoundProgress extends FieldPluginBase {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
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
    $written_reviews_round_nid = $values->nid ?? 0;

    $value = 0;

    if ($this->moduleHandler->moduleExists('simple_school_reports_reviews')) {
      /** @var \Drupal\simple_school_reports_reviews\Service\WrittenReviewsRoundProgressServiceInterface $progress_service */
      $progress_service = \Drupal::service('simple_school_reports_reviews.written_reviews_round_progress_service');
      $value = $progress_service->getProgress($written_reviews_round_nid);
    }

    $build = [];
    $build['value'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [],
      ],
      '#value' => $value . ' %',
    ];

    $cache = new CacheableMetadata();
    $cache->addCacheTags(['node:' . $written_reviews_round_nid]);
    $cache->addCacheContexts(['route']);
    $cache->applyTo($build);
    return $build;
  }

}
