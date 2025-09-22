<?php

namespace Drupal\simple_school_reports_core_gy\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\Service\FileTemplateService;

/**
 * Course data service for Gymnasiet (2025).
 */
class CourseDataServiceGy25 implements CourseDataServiceGyInterface {

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
    $cid = 'course_data_gy25';
    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $path = $this->moduleHandler->getModuleDirectories()['simple_school_reports_core_gy'] . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'course_catalog_gy25.csv';
    if (!file_exists($path)) {
      return [];
    }

    $language_code_subjects = [
      'MODY',
      'MODG',
      'MODF',
      'MODO',
      'MODE',
    ];

    $first_row = TRUE;

    $course_data = [];

    $handle = fopen($path, 'r');
    while (($row = fgetcsv($handle)) !== FALSE) {
      $row = FileTemplateService::trimCsvRow($row);
      // Validate the first row.
      if ($first_row) {
        $first_row = FALSE;

        // "Course","Course code","Subject","Subject code","Link","Points"
        if (count($row) < 5 || $row[0] !== 'Course' || $row[1] !== 'Course code' || $row[2] !== 'Subject' || $row[3] !== 'Subject code' || $row[4] !== 'Link' || $row[5] !== 'Points' || $row[6] !== 'Levels') {
          break;
        }
        continue;
      }

      $course_name = $row[0];
      $course_code = $row[1];
      $subject_name = $row[2];
      $subject_code = $row[3];
      $subject_link = $row[4];
      $points = $row[5];
      $levels = $row[6];

      if (!empty($levels)) {
        $levels = explode(',', $levels);
        $levels = array_map('trim', $levels);
        $levels = array_filter($levels, function ($level) {
          return !empty($level);
        });
      }
      else {
        $levels = [];
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
        'levels' => $levels,
        'points' => (int) $points,
        'school_type_versioned' => 'GY:2025',
      ];
    }
    fclose($handle);

    $programmes = $this->entityTypeManager->getStorage('ssr_programme')->loadByProperties([
      'school_type_versioned' => 'GY:2025',
      'type' => 'programme',
      'status' => TRUE,
    ]);
    /** @var \Drupal\simple_school_reports_entities\ProgrammeInterface $programme */
    foreach ($programmes as $programme) {
      $programme_code = $programme->get('code')->value;
      if (!$programme_code || !$programme->label()) {
        continue;
      }
      $course_code = 'GYAR' . $programme_code;
      $course_data[$course_code]    = [
        'label' => 'Gymnasiearbete - ' . $programme->label(),
        'course_code' => $course_code,
        'subject_code' => 'GYAR',
        'subject_name' => 'Gymnasiearbete',
        'link' => '',
        'use_langcode' => FALSE,
        'official' => TRUE,
        'grade_vid' => 'af_grade_system',
        'group_for' => [],
        'levels' => [],
        'points' => 100,
        'school_type_versioned' => 'GY:2025',
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
    $cid = 'subjects_data_gy25';
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
      ->condition('field_school_type_versioned', ['GY:2025'], 'IN')
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
