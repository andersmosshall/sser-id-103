<?php

namespace Drupal\simple_school_reports_core_gr\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\Service\FileTemplateService;

/**
 * Course data service for Grundskolan.
 */
class CourseDataServiceGr implements CourseDataServiceGrInterface {

  use StringTranslationTrait;

  protected array $lookup = [];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileSystemInterface $fileSystem,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getCourseData(): array {
    $cid = 'course_data_gr22';
    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $path = $this->moduleHandler->getModuleDirectories()['simple_school_reports_core_gr'] . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'course_catalog_gr22.csv';
    if (!file_exists($path)) {
      return [];
    }

    $first_row = TRUE;

    $course_data = [];
    $language_code_subjects = ['M1', 'M2', 'ML'];
    $no_subjects = ['BI', 'KE', 'FY'];
    $so_subjects = ['RE', 'SH', 'HI', 'GE'];

    $no_courses = [];
    $so_courses = [];

    $handle = fopen($path, 'r');
    while (($row = fgetcsv($handle)) !== FALSE) {
      $row = FileTemplateService::trimCsvRow($row);
      // Validate the first row.
      if ($first_row) {
        $first_row = FALSE;
        if (count($row) < 5 || $row[0] !== 'Course' || $row[1] !== 'Course code' || $row[2] !== 'Subject' || $row[3] !== 'Subject code' || $row[4] !== 'Link') {
          break;
        }
        continue;
      }

      $course_name = $row[0];
      $course_code = $row[1];
      $subject_name = $row[2];
      $subject_code = $row[3];
      $subject_link = $row[4];

      if (in_array($subject_code, $no_subjects)) {
        // Group course for NO.
        $no_courses[$course_code] = $course_code;
      }

      if (in_array($subject_code, $so_subjects)) {
        // Group course for SO.
        $so_courses[$course_code] = $course_code;
      }

      $course_data[$course_code] = [
        'label' => $course_name,
        'course_code' => $course_code,
        'subject_code' => $subject_code,
        'subject_name' => $subject_name,
        'link' => $subject_link,
        'use_langcode' => in_array($subject_code, $language_code_subjects),
        'official' => TRUE,
        'grade_vid' => 'af_grade_system',
        'group_for' => [],
        'levels' => [],
        'school_type_versioned' => 'GR:22',
      ];
    }
    fclose($handle);

    // Add group courses, e.g. NO, SO.
    if (!empty($no_courses)) {
      $course_data['GRNO'] = [
        'label' => 'Naturorienterande ämnen',
        'course_code' => 'GRNO',
        'subject_code' => 'NO',
        'subject_name' => 'Naturorienterande ämnen',
        'link' => NULL,
        'use_langcode' => FALSE,
        'official' => FALSE,
        'grade_vid' => 'af_grade_system',
        'group_for' => array_values($no_courses),
        'levels' => [],
        'school_type_versioned' => 'GR:22',
      ];
    }

    if (!empty($so_courses)) {
      $course_data['GRSO'] = [
        'label' => 'Samhällsorienterande ämnen',
        'course_code' => 'GRSO',
        'subject_code' => 'SO',
        'subject_name' => 'Samhällsorienterande ämnen',
        'link' => NULL,
        'use_langcode' => FALSE,
        'official' => FALSE,
        'grade_vid' => 'af_grade_system',
        'group_for' => array_values($so_courses),
        'levels' => [],
        'school_type_versioned' => 'GR:22',
      ];
    }

    if (!empty($no_courses) && !empty($so_courses)) {
      $course_data['GRNOSO'] = [
        'label' => 'NO/SO',
        'course_code' => 'GRNOSO',
        'subject_code' => 'CNSO',
        'subject_name' => 'NO/SO',
        'link' => NULL,
        'use_langcode' => FALSE,
        'official' => FALSE,
        'grade_vid' => 'none',
        'group_for' => array_merge(array_values($no_courses), array_values($so_courses)),
        'levels' => [],
        'school_type_versioned' => 'GR:22',
      ];
    }

    if (\Drupal::moduleHandler()->moduleExists('simple_school_reports_absence_make_up')) {
      $course_data['GRCBT'] = [
        'label' => 'Bonustimme',
        'course_code' => 'GRCBT',
        'subject_code' => 'CBT',
        'subject_name' => 'Bonustimme',
        'link' => NULL,
        'use_langcode' => FALSE,
        'official' => FALSE,
        'grade_vid' => 'none',
        'group_for' => [],
        'levels' => [],
        'school_type_versioned' => 'GR:22',
      ];
    }

    // Sort by course label keep keys.
    uasort($course_data, function ($a, $b) {
      return strnatcmp($a['label'], $b['label']);
    });

    $this->lookup[$cid] = $course_data;
    return $course_data;
  }

  public function getSubjectsData(): array {
    $cid = 'subjects_data_gr22';
    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    // Start taking subjects from the course list.
    $subjects_data = [];

    foreach ($this->getCourseData() as $course_data) {
      $subjects_data[$course_data['subject_code']] = [
        'subject_code' => $course_data['subject_code'],
        'subject_name' => $course_data['subject_name'],
      ];
    }

    // Add some hard coded special subjects.
    $subjects_data['COA'] = [
      'subject_code' => 'COA',
      'subject_name' => 'Övriga ämnen',
    ];

    // Add any subjects that has been created in school subjects vocabulary.
    $tids = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'school_subject')
      ->condition('field_school_type_versioned', ['GR:22'], 'IN')
      ->exists('field_subject_code_new')
      ->condition('field_subject_code_new', array_keys($subjects_data), 'NOT IN')
      ->execute();

    /** @var \Drupal\taxonomy\TermInterface $school_subject_terms */
    $school_subject_terms = !empty($tids)
      ? $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($tids)
      : [];

    foreach ($school_subject_terms as $school_subject_term) {
      $subjects_data[$school_subject_term->get('field_subject_code_new')->value] = [
        'subject_code' => $school_subject_term->get('field_subject_code_new')->value,
        'subject_name' => $school_subject_term->label(),
      ];
    }

    uasort($subjects_data, function ($a, $b) {
      return strnatcmp($a['subject_name'], $b['subject_name']);
    });
    $this->lookup[$cid] = $subjects_data;
    return $subjects_data;
  }
}
