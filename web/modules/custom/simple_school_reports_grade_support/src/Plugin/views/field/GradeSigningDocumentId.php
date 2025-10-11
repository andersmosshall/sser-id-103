<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\DaysLeft
 */

namespace Drupal\simple_school_reports_grade_support\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\simple_school_reports_grade_support\GradeSigningInterface;
use Drupal\views\Annotation\ViewsField;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show grade signing document id.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("ssr_grade_signing_document_id")
 */
class GradeSigningDocumentId extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $cache = new CacheableMetadata();
    $build = [];
    $grade_signing = $values->_entity;
    if (!$grade_signing || !$grade_signing instanceof GradeSigningInterface) {
      $cache->applyTo($build);
      return $build;
    }
    $cache->addCacheableDependency($grade_signing);
    $build['document_id'] = [
      '#plain_text' => $grade_signing->getDocumentId(),
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
