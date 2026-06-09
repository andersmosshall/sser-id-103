<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_child_care_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareSchemaServiceInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Field handler to show child care overview.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("child_care_day_overview")
 */
class ChildCareDayOverviewStudent extends FieldPluginBase {

  protected ChildCareSchemaServiceInterface $childCareSchemaService;

  protected Request $request;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->childCareSchemaService = $container->get('simple_school_reports_child_care_support.child_care_schema');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
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
    $cache->addCacheTags([
      'school_week_list',
      'node_list:day_absence',
      'school_week_deviation_list',
      'ssr_school_week_per_grade',
      'ssr_child_care_list',
      'ssr_child_care_placement_list',
      'ssr_child_care_schema_list',
      'ssr_cc_deviation_list',
      'ssr_cc_deviation_student_list',
    ]);
    $cache->addCacheContexts(['url.query_args:date', 'url.query_args:groups', 'route']);
    $uid = $values->uid ?? 0;
    $build = [];

    $date = $this->request->query->get('date');
    try {
      $date_object = new \DateTime($date);
    }
    catch (\Exception $e) {
      $cache->applyTo($build);
      return $build;
    }

    $groups = $this->request->query->get('groups');
    if (is_string($groups) && $groups !== '') {
      $groups = explode(',', $groups);
    }
    if (empty($groups)) {
      $cache->applyTo($build);
      return $build;
    }

    $build['value'] = $this->childCareSchemaService->buildStudentDayOverview($uid, $date_object, $groups);

    $cache->applyTo($build);
    return $build;
  }

}
