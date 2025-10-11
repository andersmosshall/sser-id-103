<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_entities\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_entities\SyllabusInterface;
use Drupal\views\Annotation\ViewsField;
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

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['ssr_syllabus_list']);
    $build = [];
    $syllabus = $values->_entity;
    if (!$syllabus || !$syllabus instanceof SyllabusInterface) {
      $cache->applyTo($build);
      return $build;
    }
    $cache->addCacheableDependency($syllabus);

    $level_names = array_column($syllabus->get('levels_display')->getValue(), 'value');

    if (empty($level_names)) {
      $cache->applyTo($build);
      return $build;
    }

    $build['levels'] = [
      '#theme' => 'item_list',
      '#items' => $level_names,
    ];
    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This function exists to override parent query function.
    // Do nothing.
  }

}
