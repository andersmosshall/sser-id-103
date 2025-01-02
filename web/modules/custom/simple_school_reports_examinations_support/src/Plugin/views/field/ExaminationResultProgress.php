<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_examinations_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\simple_school_reports_examinations_support\Service\ExaminationService;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show progress in examination results.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("examination_result_progress")
 */
class ExaminationResultProgress extends FieldPluginBase {

  /**
   * The module handler.
   */
  protected $moduleHandler;

  /**
   * The examination service.
   */
  protected ExaminationService $examinationService;

  /**
   * The route match.
   */
  protected RouteMatchInterface $routeMatch;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->examinationService = $container->get('simple_school_reports_examinations_support.examination_service');
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
    $examination_id = $values->id ?? 0;
    $assessment_group_id = $this->routeMatch->getRawParameter('ssr_assessment_group') ?? 0;

    $value = 0;

    if ($this->moduleHandler->moduleExists('simple_school_reports_examinations')) {
      $value = $this->examinationService->getProgress($examination_id);
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
    $cache->addCacheTags(['ssr_assessment_group:' . $assessment_group_id, 'ssr_examination:' . $examination_id, 'ssr_examination_result_list']);
    $cache->applyTo($build);
    return $build;
  }

}
