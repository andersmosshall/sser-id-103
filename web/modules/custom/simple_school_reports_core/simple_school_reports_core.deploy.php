<?php

use Drupal\simple_school_reports_core\SchoolSubjectHelper;

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_core_deploy_9001() {
  SchoolSubjectHelper::importSubjects();
}

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_core_deploy_9002() {
  SchoolSubjectHelper::importGrades();
}

/**
 * Update school subjects, e.g. set GR:22 for previously unset subjects..
 */
function simple_school_reports_core_deploy_10001() {
  // Deprecated hook, this is now handled by the GR core module.

  /** @var \Drupal\taxonomy\TermStorage $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $tids = $term_storage
    ->getQuery()
    ->condition('vid', 'school_subject')
    ->accessCheck(FALSE)
    ->execute();

  if (!empty($tids)) {
    foreach ($tids as $tid) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $term_storage->load($tid);
      if (!$term) {
        continue;
      }

      if ($term->get('field_school_type_versioned')->isEmpty()) {
        $term->set('field_school_type_versioned', 'GR:22');
      }
      if ($term->get('field_subject_code_new')->isEmpty()) {
        $subject_code = $term->get('field_subject_code')->value;
        $term->set('field_subject_code_new', $subject_code ?? NULL);
        if ($term->label() === 'Övrigt') {
          $term->set('name', 'Övriga ämnen');
          $term->set('field_subject_code_new',  'COA');
        }
        if ($term->label() === 'NO/SO') {
          $term->set('field_subject_code_new',  'CNSO');
        }
        if ($term->label() === 'Kristendom') {
          $term->set('field_subject_code_new',  'CKR');
        }
        if ($term->label() === 'Livskunskap') {
          $term->set('field_subject_code_new',  'CLV');
        }
        if ($term->label() === 'Fritids') {
          $term->set('field_subject_code_new',  'CFT');
        }
        if ($term->label() === 'Elevens val') {
          $term->set('field_subject_code_new',  'CEV');
        }
      }
      $term->save();
    }
  }
}
