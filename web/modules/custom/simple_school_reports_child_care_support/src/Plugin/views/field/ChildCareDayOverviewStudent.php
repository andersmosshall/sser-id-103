<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_child_care_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareSchemaServiceInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
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

  protected ChildCareServiceInterface $childCareService;

  protected Request $request;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->childCareSchemaService = $container->get('simple_school_reports_child_care_support.child_care_schema');
    $instance->childCareService = $container->get('simple_school_reports_child_care_support.child_care');
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
    $uid = $values->uid ?? 0;
    $build = [];

    $date = $this->childCareService->getDateFromRequest();
    $groups = $this->childCareService->getChildCareIdsFromRequest();
    $cache = $this->childCareSchemaService->getCacheableMetadata($date);

    if (!$date || empty($groups)) {
      $cache->applyTo($build);
      return $build;
    }

    $build['value'] = $this->childCareSchemaService->buildStudentDayOverview($uid, $date, $groups);

    $cache->applyTo($build);
    return $build;
  }

}
