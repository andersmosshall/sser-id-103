<?php

use Drupal\simple_school_reports_core\SchoolSubjectHelper;

/**
 * Removed deploy hook, 9001.
 */
function simple_school_reports_core_deploy_9001() {
  // Deprecated hook, this is now handled by the GR core module.
}

/**
 * Import grades.
 */
function simple_school_reports_core_deploy_9002() {
  $grades = [
    'geg_grade_system' => [
      'G' => [
        'field_merit' => 10.00,
      ],
      'EG' => [
        'field_merit' => 0.00,
      ],
    ],
    'af_grade_system' => [
      'A' => [
        'field_merit' => 20.00,
      ],
      'B' => [
        'field_merit' => 17.50,
      ],
      'C' => [
        'field_merit' => 15.00,
      ],
      'D' => [
        'field_merit' => 12.50,
      ],
      'E' => [
        'field_merit' => 10.00,
      ],
      'F' => [
        'field_merit' => 0.00,
      ],
      '-' => [
        'field_merit' => 0.00,
      ],
    ],
  ];

  /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
  $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  foreach ($grades as $vid => $grade_data) {
    $map = [];
    $terms = $termStorage->loadTree($vid, 0, NULL, TRUE);

    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $map[$term->label()] = $term->id();
    }

    foreach ($grade_data as $name => $fields) {
      if (isset($map[$name])) {
        continue;
      }

      $term = $termStorage->create([
        'name' => $name,
        'vid' => $vid,
        'langcode' => 'sv',
        'status' => 1,
      ]);

      foreach ($fields as $field => $value) {
        $term->set($field, $value);
      }

      $term->save();
    }
  }
}

/**
 * Removed deploy hook, 10001.
 */
function simple_school_reports_core_deploy_10001() {
  // Deprecated hook, this is now handled by the GR core module.
}
