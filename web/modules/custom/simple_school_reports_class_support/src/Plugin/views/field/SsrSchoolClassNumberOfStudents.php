<?php

namespace Drupal\simple_school_reports_class_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\simple_school_reports_class_support\SchoolClassInterface;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * A handler to provide proper displays for number of students in a class.
 *
 * @ViewsField("ssr_school_class_number_of_students")
 */
class SsrSchoolClassNumberOfStudents extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['user_list:student']);
    $build = [];
    $class = $values->_entity;
    if (!$class || !$class instanceof SchoolClassInterface) {
      $cache->applyTo($build);
      return $build;
    }
    $cache->addCacheableDependency($class);
    $number_of_student = $class->get('number_of_students')->value ?? 0;
    $build['#markup'] = $number_of_student;
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
