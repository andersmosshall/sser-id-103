<?php

use Drupal\simple_school_reports_core\Form\ActivateSyllabusFormBase;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\simple_school_reports_core_gy\ProgrammesSyncHelper;


/**
 * Import subjects base for GY 11.
 */
function simple_school_reports_core_gy_deploy_10001() {
  /** @var \Drupal\taxonomy\TermStorage $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $connection = \Drupal::database();

  $school_type = 'GY:2011';
  $vid = 'school_subject';
  $query = $connection->select('taxonomy_term_field_data', 't');
  $query->leftJoin('taxonomy_term__field_subject_code_new', 'sc', 'sc.entity_id = t.tid');
  $query->innerJoin('taxonomy_term__field_school_type_versioned', 'st', 'st.entity_id = t.tid');
  $query->condition('t.vid', $vid);
  $query->condition('st.field_school_type_versioned_value', [$school_type], 'IN');
  $query->fields('t', ['tid']);
  $query->fields('sc', ['field_subject_code_new_value']);
  $results = $query->execute();

  $subject_code_exist = [];
  foreach ($results as $result) {
    if ($result->field_subject_code_new_value) {
      $subject_code_exist[$result->field_subject_code_new_value] = TRUE;
    }
  }

  // Now create subjects.
  $to_import = [
    'COA' => 'Övriga ämnen',
  ];
  $mandatory = [];
  $variants = [];

  foreach ($to_import as $code => $label) {
    if (empty($subject_code_exist[$code])) {
      $variants = $variants[$code] ?? [''];

      foreach ($variants as $variant) {
        $term_label = $label;
        if (!empty($variant)) {
          $term_label .= ' ' . mb_strtolower($variant);
        }
        $status = 1;
        $term = $term_storage->create([
          'name' => $term_label,
          'vid' => $vid,
          'langcode' => 'sv',
          'field_subject_code_new' => $code,
          'field_school_type_versioned' => $school_type,
          'status' => $status,
        ]);

        if (isset($mandatory[$code])) {
          $term->set('field_mandatory', TRUE);
        }

        if (!empty($variant)) {
          $term->set('field_subject_specify', $variant);
        }

        $term->save();
      }
    }
  }
}

/**
 * Import subjects base for GY 25.
 */
function simple_school_reports_core_gy_deploy_10002() {
  /** @var \Drupal\taxonomy\TermStorage $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $connection = \Drupal::database();

  $school_type = 'GY:2025';
  $vid = 'school_subject';
  $query = $connection->select('taxonomy_term_field_data', 't');
  $query->leftJoin('taxonomy_term__field_subject_code_new', 'sc', 'sc.entity_id = t.tid');
  $query->innerJoin('taxonomy_term__field_school_type_versioned', 'st', 'st.entity_id = t.tid');
  $query->condition('t.vid', $vid);
  $query->condition('st.field_school_type_versioned_value', [$school_type], 'IN');
  $query->fields('t', ['tid']);
  $query->fields('sc', ['field_subject_code_new_value']);
  $results = $query->execute();

  $subject_code_exist = [];
  foreach ($results as $result) {
    if ($result->field_subject_code_new_value) {
      $subject_code_exist[$result->field_subject_code_new_value] = TRUE;
    }
  }

  // Now create subjects.
  $to_import = [
    'COA' => 'Övriga ämnen',
  ];
  $mandatory = [];
  $variants = [];

  foreach ($to_import as $code => $label) {
    if (empty($subject_code_exist[$code])) {
      $variants = $variants[$code] ?? [''];

      foreach ($variants as $variant) {
        $term_label = $label;
        if (!empty($variant)) {
          $term_label .= ' ' . mb_strtolower($variant);
        }
        $status = 1;
        $term = $term_storage->create([
          'name' => $term_label,
          'vid' => $vid,
          'langcode' => 'sv',
          'field_subject_code_new' => $code,
          'field_school_type_versioned' => $school_type,
          'status' => $status,
        ]);

        if (isset($mandatory[$code])) {
          $term->set('field_mandatory', TRUE);
        }

        if (!empty($variant)) {
          $term->set('field_subject_specify', $variant);
        }

        $term->save();
      }
    }
  }
}

/**
 * Import programmes for GY 11.
 */
function simple_school_reports_core_gy_deploy_10003() {
  ProgrammesSyncHelper::syncProgrammes('GY:2011');
}

/**
 * Import programmes for GY 25.
 */
function simple_school_reports_core_gy_deploy_10004() {
  ProgrammesSyncHelper::syncProgrammes('GY:2025');
}
