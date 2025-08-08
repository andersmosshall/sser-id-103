<?php

use Drupal\simple_school_reports_core_gr\Form\ActivateSyllabusFormGr;
use Drupal\simple_school_reports_core\Form\ActivateSyllabusFormBase;

/**
 * Create Bonustimme subject.
 */
function simple_school_reports_absence_make_up_deploy_9001() {
  /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $connection = \Drupal::database();

  $vid = 'school_subject';
  $query = $connection->select('taxonomy_term_field_data', 't');
  $query->leftJoin('taxonomy_term__field_subject_code_new', 'sc', 'sc.entity_id = t.tid');
  $query->innerJoin('taxonomy_term__field_school_type_versioned', 'st', 'st.entity_id = t.tid');
  $query->condition('t.vid', $vid);
  $query->condition('st.field_school_type_versioned_value', ['GR:22'], 'IN');
  $query->fields('t', ['tid']);
  $query->fields('sc', ['field_subject_code_new_value']);
  $results = $query->execute();

  $subject_code_exist = [];
  foreach ($results as $result) {
    if ($result->field_subject_code_new_value) {
      $subject_code_exist[$result->field_subject_code_new_value] = TRUE;
    }
  }

  $to_import = [
    'CBT' => 'Bonustimme',
  ];
  $mandatory = [];
  $variants = [];

  /**
   * Create links in personalisation group to medlemsform.
   * $type is not a fully loaded term, BTW.
   */
  foreach ($to_import as $code => $label) {
    if (empty($subject_code_exist[$code])) {
      $variants = $variants[$code] ?? [''];

      foreach ($variants as $variant) {
        $term_label = $label;
        if (!empty($variant)) {
          $term_label .= ' ' . mb_strtolower($variant);
        }
        $status = 1;
        if ($code === 'SL' && !empty($variant)) {
          $status = 0;
        }
        if ($code === 'SVA' || $code === 'TN') {
          $status = 0;
        }
        $term = $term_storage->create([
          'name' => $term_label,
          'vid' => $vid,
          'langcode' => 'sv',
          'field_subject_code_new' => $code,
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
 * Create Bonustimme syllabus.
 */
function simple_school_reports_absence_make_up_deploy_9002() {
  $connection = \Drupal::database();

  /** @var \Drupal\simple_school_reports_core_gr\Service\CourseDataServiceGrInterface $course_data_service */
  $course_data_service = \Drupal::service('simple_school_reports_core_gr.course_data');

  $syllabus_exists = [];
  $query = $connection->select('ssr_syllabus_field_data', 's');
  $query->fields('s', ['course_code']);
  $results = $query->execute();
  foreach ($results as $result) {
    $syllabus_exists[$result->course_code] = TRUE;
  }

  $data = $course_data_service->getCourseData()['GRCBT'];
  if (empty($data)) {
    return;
  }
  $course_data = [
    'GRCBT' => $data,
  ];

  foreach ($course_data as $course_code => $data) {
    if (!empty($data['use_langcode'])) {
      continue;
    }
    if (!empty($syllabus_exists[$course_code])) {
      continue;
    }

    $data['short_label'] = ActivateSyllabusFormGr::calculateCourseShortLabel($data['subject_code'], $data['language_code'] ?? NULL);

    $stored = ActivateSyllabusFormBase::activateCourse($course_code, $data);
    if (!$stored) {
      \Drupal::logger('simple_school_reports_core_gr')->error('Failed to activate course ' . $course_code);
    }
  }
}
