<?php

use Drupal\simple_school_reports_core\Form\ActivateSyllabusFormBase;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\simple_school_reports_core_gr\Form\ActivateSyllabusFormGr;

/**
 * Import subjects base for GR.
 */
function simple_school_reports_core_gr_deploy_10001() {
  /** @var \Drupal\taxonomy\TermStorage $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $connection = \Drupal::database();

  // Take the opportunity to update subjects for old ssr systems.
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

      // No school type means GR:22 course..
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

  // Now create subjects.
  $to_import = [
    'BL' => 'Bild',
    'BI' => 'Biologi',
    'EN' => 'Engelska',
    'FY' => 'Fysik',
    'GE' => 'Geografi',
    'HI' => 'Historia',
    'HKK' => 'Hem- och Konsumentkunskap',
    'IDH' => 'Idrott och Hälsa',
    'KE' => 'Kemi',
    'MA' => 'Matematik',
    'MU' => 'Musik',
    'NO' => 'Naturorienterande ämnen',
    'RE' => 'Religionskunskap',
    'SH' => 'Samhällskunskap',
    'SL' => 'Slöjd',
    'SO' => 'Samhällsorienterande ämnen',
    'SV' => 'Svenska',
    'SVA' => 'Svenska som andraspråk',
    'TK' => 'Teknik',
    'TN' => 'Teckenspråk',
    'COA' => 'Övriga ämnen',
  ];
  $mandatory = [
    'SV' => TRUE,
    'EN' => TRUE,
    'MA' => TRUE,
  ];
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
        $term_map[$code] = $term;
      }
    }
  }

  $block_parent = [
    'BI' => 'NO',
    'FY' => 'NO',
    'KE' => 'NO',
    'GE' => 'SO',
    'HI' => 'SO',
    'RE' => 'SO',
    'SH' => 'SO',
  ];

  foreach ($block_parent as $code => $parent_code) {
    if (!empty($term_map[$code]) && !empty($term_map[$parent_code])) {
      $term_map[$code]->set('field_block_parent', $term_map[$parent_code]);
      $term_map[$code]->save();
    }
  }
}

/**
 * Import syllabus base for GR.
 */
function simple_school_reports_core_gr_deploy_10002() {
  /** @var \Drupal\taxonomy\TermStorage $term_storage */
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $connection = \Drupal::database();

  $course_codes_to_skip = [
    'GRCBT',
  ];
  /** @var \Drupal\simple_school_reports_core_gr\Service\CourseDataServiceGrInterface $course_data_service */
  $course_data_service = \Drupal::service('simple_school_reports_core_gr.course_data');

  $syllabus_exists = [];
  $query = $connection->select('ssr_syllabus_field_data', 's');
  $query->fields('s', ['course_code']);
  $results = $query->execute();
  foreach ($results as $result) {
    $syllabus_exists[$result->course_code] = TRUE;
  }

  $course_data = $course_data_service->getCourseData();

  // Take official courses first.
  uasort($course_data, function ($a, $b) {
    if ($a['official'] === $b['official']) {
      return 0;
    }
    return $a['official'] ? -1 : 1;
  });

  foreach ($course_data as $course_code => $data) {
    if (in_array($course_code, $course_codes_to_skip)) {
      continue;
    }
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

  // Take the opportunity to create syllabuses for previous created subjects in
  // old SSR systems.

  $course_data_by_subject_code = [];
  foreach ($course_data as $data) {
    $course_data_by_subject_code[$data['subject_code']] = $data;
  }

  // Get all subjects that are not connected to a syllabus.
  $subjects_in_syllabuses = [];

  $query = $connection->select('ssr_syllabus_field_data', 's');
  $query->fields('s', ['school_subject']);
  $results = $query->execute();
  foreach ($results as $result) {
    $subjects_in_syllabuses[] = $result->school_subject;
  }
  if (empty($subjects_in_syllabuses)) {
    return;
  }

  $subject_tids_to_handle = $term_storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('vid', 'school_subject')
    ->condition('tid', $subjects_in_syllabuses, 'NOT IN')
    ->condition('field_school_type_versioned', ['GR:22'], 'IN')
    ->exists('field_subject_code_new')
    ->execute();

  $language_label_map = SchoolSubjectHelper::getSupportedLanguageCodes(FALSE);

  $course_codes_to_skip[] = 'GRSL';
  $course_codes_to_skip[] = 'GRGRSLJ01';

  foreach ($subject_tids_to_handle as $tid) {
    /** @var \Drupal\taxonomy\TermInterface $school_subject */
    $school_subject = $term_storage->load($tid);

    $subject_code = $school_subject->get('field_subject_code_new')->value;
    if (!$subject_code) {
      continue;
    }

    $language_code = $school_subject->get('field_language_code')->value ?? NULL;
    $course_data = $course_data_by_subject_code[$subject_code] ?? NULL;
    if (!$course_data) {
      $course_code = 'GR' . mb_strtoupper($subject_code);
      if ($language_code) {
        $course_code .= '_' . $language_code;
      }

      $course_data = [
        'label' => $school_subject->label(),
        'course_code' => $course_code,
        'subject_code' => $subject_code,
        'subject_name' => $school_subject->label(),
        'link' => NULL,
        'use_langcode' => $language_code !== NULL,
        'language_code' => $language_code,
        'official' => FALSE,
        'custom' => TRUE,
        'grade_vid' => 'none',
        'group_for' => [],
        'levels' => [],
        'school_type_versioned' => $school_subject->get('field_school_type_versioned')->value ?? 'GR:22',
      ];
    }
    else {
      if ($language_code) {
        $language_label = $language_label_map[$language_code] ?? '?';
        $course_data['label'] .= ', ' . $language_label;
        $course_data['course_code'] .= '_' . $language_code;
        $course_data['language_code'] = $language_code;
        $course_data['subject_name'] .= ', ' . $language_label;
      }
    }

    if ($language_code) {
      $course_data['language_code'] = $language_code;
    }

    $course_code = $course_data['course_code'];

    if (in_array($course_code, $course_codes_to_skip)) {
      continue;
    }
    $course_data['subject_target_id'] = $school_subject->id();
    $course_data['short_label'] = ActivateSyllabusFormGr::calculateCourseShortLabel($subject_code, $language_code);
    $course_data['status'] = $school_subject->isPublished();
    $stored = ActivateSyllabusFormBase::activateCourse($course_code, $course_data);
    if (!$stored) {
      \Drupal::logger('simple_school_reports_core_gr')->error('Failed to activate course ' . $course_code);
    }
  }

  // Take the opportunity to queue all courses for updates.
  $course_nids = \Drupal::entityTypeManager()->getStorage('node')
    ->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'course')
    ->execute();

  $queue = \Drupal::service('queue')->get('ssr_update_course');
  $queue->createQueue();
  foreach ($course_nids as $id) {
    $queue->createItem(['course_nid' => $id]);
  }
}
