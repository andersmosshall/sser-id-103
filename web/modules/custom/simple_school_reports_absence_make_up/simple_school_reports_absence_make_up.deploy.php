<?php

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_absence_make_up_deploy_9001() {
  // @ToDO: Convert to import CBT as a syllabus.

  $vid = 'school_subject';
  /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
  $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');


  $subjects = $termStorage->loadTree($vid, 0, NULL, TRUE);
  $subject_code_exist = [];
  foreach ($subjects as $subject) {
    if ($subject->get('field_subject_code_new')->value) {
      $subject_code_exist[$subject->get('field_subject_code_new')->value] = TRUE;
    }
  }

  $to_import = [
    'CBT' => 'Bonustimme',
  ];

  $mandatory = [];
  $variants = [];
  $term_map = [];

  /**
   * Create links in personalisation group to medlemsform.
   * $type is not a fully loaded term, BTW.
   */
  foreach ($to_import as $code => $label) {
    if (empty($subject_code_exist[$code])) {
      $variants = $variants[$code] ?? [''];
      foreach ($variants as $variant) {
        $term = $termStorage->create([
          'name' => $label,
          'vid' => $vid,
          'langcode' => 'sv',
          'field_subject_code_new' => $code,
        ]);

        if (isset($mandatory[$code])) {
          $term->set('field_mandatory', TRUE);
        }

        if (!empty($variant)) {
          $term->set('field_subject_specify', $variant);
        }

        $term->save();
        $term_map[$code] = $term;
      }
    }
  }
}
