<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_entities\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to show syllabys levels.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("syllabus_levels")
 */
class SyllabusLevels extends FieldPluginBase {

  protected EntityTypeManagerInterface $entityTypeManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
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
    $syllabus = $values->_entity;
    $cache = new CacheableMetadata();

    $build = [];

    if (!empty($syllabus)) {
      $cache->addCacheableDependency($syllabus);
      $level_ids = array_column($syllabus->get('levels')->getValue(), 'target_id');

      $names = [];
      foreach ($level_ids as $level_id) {
        $level = $this->entityTypeManager->getStorage('ssr_syllabus')->load($level_id);
        if ($level) {
          $names[] = $level->label();
          $cache->addCacheableDependency($level);
        }
      }

      if (!empty($names)) {
        $build = [
          '#theme' => 'item_list',
          '#items' => $names,
        ];
      }
    }

    $cache->applyTo($build);
    return $build;
  }

}
