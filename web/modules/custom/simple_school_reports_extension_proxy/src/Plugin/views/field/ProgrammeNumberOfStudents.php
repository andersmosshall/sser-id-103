<?php

namespace Drupal\simple_school_reports_extension_proxy\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\simple_school_reports_entities\ProgrammeInterface;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * A handler to provide proper displays for number of students in a programme.
 *
 * @ViewsField("ssr_programme_number_of_students")
 */
class ProgrammeNumberOfStudents extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $cache = new CacheableMetadata();
    $cache->addCacheTags(['user_list:student']);
    $build = [];
    $programme = $values->_entity;
    if (!$programme || !$programme instanceof ProgrammeInterface || !\Drupal::hasService('simple_school_reports_entities.programme_service')) {
      $cache->applyTo($build);
      return $build;
    }
    /** @var \Drupal\simple_school_reports_entities\Service\ProgrammeServiceInterface $programme_service */
    $programme_service =  \Drupal::service('simple_school_reports_entities.programme_service');
    $student_uids = $programme_service->getStudentIdsByProgrammeId($programme->id());
    $build['#markup'] = count($student_uids);
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
