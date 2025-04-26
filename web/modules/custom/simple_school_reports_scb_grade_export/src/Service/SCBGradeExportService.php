<?php

namespace Drupal\simple_school_reports_scb_grade_export\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\SSRVersionServiceInterface;
use Drupal\simple_school_reports_grade_registration\GroupGradeExportInterface;

/**
 * Class SCBGradeExportService
 *
 * THIS SERVICE IS CONSISTENT WITH SCB SPEC FOR:
 * - AK 6: 2024-12-13 (SEE FILE IN REFERENCE DIR)
 * - AK 9: 2024-12-12 (SEE FILE IN REFERENCE DIR)
 *
 * VERIFY SPEC. EVERY YEAR!
 * LAST VERIFIED: 2025-02-12!
 */
class SCBGradeExportService implements GroupGradeExportInterface {

  const SUBJECT_NOT_SUPPORTED = 'subject_not_supported';


  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Pnum $pnumService,
    protected EmailServiceInterface $emailService,
    protected FileSystemInterface $fileSystem,
    protected SSRVersionServiceInterface $ssrVersionService,
  ) {}

  /**
   * @inheritDoc
   */
  public function handleExport(string $student_group_nid, array $references, array &$context) {
    if (
      empty($context['results']['catalog'][$student_group_nid]) ||
      empty(Settings::get('ssr_school_unit_code')) ||
      empty(Settings::get('ssr_school_name')) ||
      empty(Settings::get('ssr_catalog_id'))
    ) {
      return;
    }

    // key: subject code, value meta data.
    $scb_subject_code_map = [
      'BL' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'EN' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'HKK' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'IDH' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'MA' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'M1' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => '2',
      ],
      'M2' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => '2',
      ],
      'ML' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => '2',
      ],
      'MU' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'NO' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => self::SUBJECT_NOT_SUPPORTED,
      ],
      'BI' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'FY' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'KE' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'SO' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => self::SUBJECT_NOT_SUPPORTED,
      ],
      'GE' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'HI' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'RE' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'SH' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'SL' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'SV' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => '2',
      ],
      'SVA' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => '2',
      ],
      'TN' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => '2',
      ],
      'TK' => [
        'scb_export_6_fallback' => 'Z',
        'scb_export_9_final_fallback' => '2',
      ],
      'OVR' => [
        'scb_export_6_fallback' => '2',
        'scb_export_9_final_fallback' => '2',
      ],
    ];

    $language_items = [
      'M2',
      'M1',
      'ML',
    ];

    $student_grades = $context['results']['catalog'][$student_group_nid];

    $ordered_student_uids = $references['ordered_student_uids'];
    foreach ($ordered_student_uids as $ordered_student_uid) {
      if (isset($student_grades[$ordered_student_uid])) {
        $student_uids[] = $ordered_student_uid;
      }
    }

    // Map the catalog_data in all catalogs.
    $catalog_data = [];
    foreach ($context['results']['catalog'] as $student_grades_data) {
     foreach ($student_grades_data as $student_uid => $student_grade_data) {
       foreach ($student_grade_data as $catalog_id => $value) {
         $catalog_data[$student_uid][$catalog_id] = $value;
       }
     }
    }

    /** @var \Drupal\user\UserInterface[] $students */
    $students = $this->entityTypeManager->getStorage('user')->loadMultiple($student_uids);

    foreach ($students as $student) {
      $student_uid = $student->id();

      $students_group_data = !empty($references['student_groups_data'][$student_group_nid])
        ? $references['student_groups_data'][$student_group_nid]
        : [];

      if (($students_group_data['grade_system'] ?? '') !== 'af_grade_system') {
        continue;
      }

      $school_grade = !empty($context['results']['ssr_student_doc_grade_value'][$student_uid])
        ? (string) $context['results']['ssr_student_doc_grade_value'][$student_uid]
        : '999';

      if ($school_grade !== '6' && $school_grade !== '9') {
        continue;
      }

      $scb_export_type = 'scb_export_' . $school_grade;
      if ($school_grade === '9') {
        $document_type = $students_group_data['document_type'] ?? '';
        $scb_export_type .= '_' . $document_type;
      }

      $scb_export_types = $references['scb_export_types'] ?? [];
      if (!in_array($scb_export_type, $scb_export_types)) {
        continue;
      }

      if (!empty($context['results'][$scb_export_type][$student_uid])) {
        continue;
      }


      $student_row_parts = [];

      // Add ssn.
      $ssn = 'ååååmmddnnnn';
      if (!$student->get('field_birth_date_source')->isEmpty()) {
        if ($student->get('field_birth_date_source')->value === 'ssn') {
          $student_ssn = $student->get('field_ssn')->value;
          if ($student_ssn) {
            $student_ssn = $this->pnumService->normalizeIfValid($student_ssn, TRUE);
            if ($student_ssn) {
              $ssn = $student_ssn;
            }
          }
        }
        else {
          $birth_date = $student->get('field_birth_date')->value;
          if ($birth_date) {
            $date = new \DateTime();
            $date->setTimestamp($birth_date);

            $ssn = $date->format('Ymd') . 'nnnn';
          }
        }
      }
      $student_row_parts[] = $this->makeRowPart($ssn, 12);

      // Add school unit code.
      $student_row_parts[] = $this->makeRowPart(Settings::get('ssr_school_unit_code'), 8);

      // Add class.
      $class = $context['results']['ssr_student_doc_class_value'][$student_uid] ?? $context['results']['ssr_student_doc_grade_value'][$student_uid] ?? '';

      $student_row_parts[] = $this->makeRowPart($class, 20);

      // Add first name.
      $first_name = $student->get('field_first_name')->value ?? '';
      $student_row_parts[] = $this->makeRowPart($first_name, 20);

      // Add last name.
      $last_name = $student->get('field_last_name')->value ?? '';
      $student_row_parts[] = $this->makeRowPart($last_name, 20);

      $has_grades = FALSE;

      // Add grade.
      foreach ($scb_subject_code_map as $scb_subject_code => $map_data) {
        $fallback = $map_data[$scb_export_type . '_fallback'] ?? self::SUBJECT_NOT_SUPPORTED;

        $catalog_id = $this->getGradeCatalogId($scb_subject_code);
        if (!empty($catalog_data[$student_uid][$catalog_id])) {
          $has_grades = TRUE;
        }
        $grade = $catalog_id && !empty($catalog_data[$student_uid][$catalog_id]) ? $catalog_data[$student_uid][$catalog_id] : $fallback;
        // If grade is '-' then set it to 9 as specified for SCB.
        if ($grade === '-') {
          $grade = '9';
        }

        if ($fallback === self::SUBJECT_NOT_SUPPORTED) {
          continue;
        }

        // Insert language item if applicable.
        if (in_array($scb_subject_code, $language_items)) {
          $language_catalog_id = $this->getSubjectCodeCatalogId($scb_subject_code);
          $language = $catalog_id && !empty($catalog_data[$student_uid][$language_catalog_id]) ? $catalog_data[$student_uid][$language_catalog_id] : '';

          if ($language === '-' || $language === '2') {
            $language = '';
          }

          $student_row_parts[] = $this->makeRowPart($language, 3);
        }
        $student_row_parts[] = $this->makeRowPart($grade, 1);
      }

      if (!$has_grades) {
        continue;
      }

      $student_row = implode(';', $student_row_parts);

      $context['results'][$scb_export_type][$student_uid] = $student_row;
    }

  }

  protected function getGradeCatalogId(string $extent_subject_code): ?int {
    $ssr_catalog_id = Settings::get('ssr_catalog_id', []);
    return $ssr_catalog_id[$extent_subject_code] ?? NULL;
  }

  protected function getSubjectCodeCatalogId(string $extent_subject_code): ?int {
    $ssr_catalog_id = Settings::get('ssr_catalog_id', []);
    return $ssr_catalog_id[$extent_subject_code . '_COM'] ?? NULL;
  }

  protected function makeRowPart(string|null $value, int $max_length): string {
    if (!$value) {
      $value = '';
    }
    $value = str_replace(';', '', $value);
    $value = trim($value);
    $value = mb_substr($value, 0, $max_length);
    return $value;
  }

  /**
   * @inheritDoc
   */
  public function beforeFinishExport(array $references, array &$context) {
    $scb_export_types = $references['scb_export_types'] ?? [];

    foreach ($scb_export_types as $scb_export_type) {
      if (empty($context['results'][$scb_export_type])) {
        continue;
      }
      $this->makeScbFile($scb_export_type, $context['results'][$scb_export_type], $references);
    }
  }

  protected function makeScbFile(string $type, array $student_rows, array $references) {
    if (empty($student_rows)) {
      return;
    }

    $now = new \DateTime();
    $export_date = $now->format('Y-m-d');

    $student_row_prefix = $this->makeRowPart('SSR', 15);
    $student_row_prefix .= ';' . $this->makeRowPart($export_date, 10);

    $ssr_version = $this->ssrVersionService->getSsrVersion();
    $student_row_prefix .= ';' .  $this->makeRowPart($ssr_version, 15);

    $file_rows = [];
    foreach ($student_rows as $student_row) {
      $full_row = $student_row_prefix . ';' . $student_row;
      $file_rows[] = $full_row;
    }
    $file_content = implode(PHP_EOL, $file_rows);

    $file_name = $type . '_' . Settings::get('ssr_school_name') . '_' . $export_date .'.txt';
    $file_name = str_replace(' ', '_', $file_name);
    $file_name = str_replace('/', '-', $file_name);
    $file_name = str_replace('\\', '-', $file_name);
    $file_name = mb_strtolower($file_name);
    $event = new FileUploadSanitizeNameEvent($file_name, '');
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch($event);
    $file_name = $event->getFilename();
    $destination = $references['base_destination'] . DIRECTORY_SEPARATOR . 'betygskalatog' . DIRECTORY_SEPARATOR;
    $destination = 'public://ssr_tmp' . DIRECTORY_SEPARATOR . $destination;

    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    $destination = $this->fileSystem->realpath($destination) . DIRECTORY_SEPARATOR;
    $final_destination = $destination . DIRECTORY_SEPARATOR . $file_name;

    file_put_contents($final_destination, $file_content);
  }

}
