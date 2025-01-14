<?php

namespace Drupal\simple_school_reports_extens_grade_export\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_core\Pnum;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_grade_registration\GroupGradeExportInterface;

/**
 * Class AbsenceStatisticsService
 *
 * @package Drupal\simple_school_reports_grade_registration\Service
 */
class ExtensGradeExportService implements GroupGradeExportInterface {
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Pnum $pnumService,
    protected EmailServiceInterface $emailService,
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * @inheritDoc
   */
  public function handleExport(string $student_group_nid, array $references, array &$context) {
    if (
      empty($context['results']['catalog'][$student_group_nid]) ||
      empty(Settings::get('ssr_school_municipality_code')) ||
      empty(Settings::get('ssr_school_unit_code')) ||
      empty(Settings::get('ssr_school_name')) ||
      empty(Settings::get('ssr_school_name_short')) ||
      empty(Settings::get('ssr_catalog_id'))
    ) {
      return;
    }

    $extent_subject_codes = [
      'BL',
      'M2',
      'M1',
      'EN',
      'HKK',
      'IDH',
      'MA',
      'ML',
      'MU',
      'NO',
      'BI',
      'FY',
      'KE',
      'SO',
      'GE',
      'HI',
      'RE',
      'SH',
      'SL',
      'SV',
      'SVA',
      'TN',
      'TK',
      'DA',
      'JU',
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
      if (!empty($context['results']['extent_student_rows'][$student_uid])) {
        continue;
      }

      $extens_export_grades = $references['extens_export_grades'] ?? [];
      $school_grade = $context['results']['ssr_student_doc_grade_value'][$student_uid] ?? '999';
      if (empty($extens_export_grades[$school_grade])) {
        continue;
      }

      $student_row = '';

      // Add class.
      $class = $context['results']['ssr_student_doc_class_value'][$student_uid] ?? $context['results']['ssr_student_doc_grade_value'][$student_uid] ??'';
      if (str_starts_with($class, 'Årskurs ')) {
        $class = str_replace('Årskurs ', '', $class);
      }

      $student_row .= $this->makeRowPart($class, 6);

      // Add filler.
      $student_row .= $this->makeRowPart('', 1);

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
      $student_row .= $this->makeRowPart($ssn, 12);

      $has_grades = FALSE;

      // Add grade.
      foreach ($extent_subject_codes as $extent_subject_code) {
        $catalog_id = $this->getGradeCatalogId($extent_subject_code);
        if (!empty($catalog_data[$student_uid][$catalog_id])) {
          $has_grades = TRUE;
        }
        $grade = $catalog_id && !empty($catalog_data[$student_uid][$catalog_id]) ? $catalog_data[$student_uid][$catalog_id] : '2';
        $student_row .= $this->makeRowPart($grade, 1);
      }
      if (!$has_grades) {
        continue;
      }

      // Add filler.
      $student_row .= $this->makeRowPart('', 8);

      // Add language.
      $language_items = [
        'M2',
        'M1',
        'ML',
      ];
      foreach ($language_items as $language_item) {
        $catalog_id = $this->getSubjectCodeCatalogId($language_item);
        $language = $catalog_id && !empty($catalog_data[$student_uid][$catalog_id]) ? $catalog_data[$student_uid][$catalog_id] : '';
        $student_row .= $this->makeRowPart($language, 3);
      }

      // Add filler.
      $student_row .= $this->makeRowPart('', 6);

      $use_contact_details = $references['extens_include_contact_details'] ?? FALSE;
      $protected_data_value = $student->get('field_protected_personal_data')->value ?? NULL;
      $has_protected_data = $protected_data_value !== NULL && $protected_data_value !== 'none';
      if ($has_protected_data) {
        $use_contact_details = FALSE;
      }

      // Add phone number.
      $phone_number = $use_contact_details ? $student->get('field_telephone_number')->value : '';
      $student_row .= $this->makeRowPart($phone_number, 12);

      // Add filler.
      $student_row .= $this->makeRowPart('', 3);

      // Add first name.
      $first_name = $student->get('field_first_name')->value ?? '';
      $student_row .= $this->makeRowPart($first_name, 20);

      // Add last name.
      $last_name = $student->get('field_last_name')->value ?? '';
      $student_row .= $this->makeRowPart($last_name, 20);

      /** @var \Drupal\paragraphs\ParagraphInterface|null $address_paragraph */
      $address_paragraph = $use_contact_details ? $student->get('field_address')->entity : NULL;

      // Add street address.
      $address = $address_paragraph?->get('field_street_address')->value ?? '';
      $student_row .= $this->makeRowPart($address, 27);

      // Added zip code.
      $zip_code = $address_paragraph?->get('field_zip_code')->value ?? '';
      $student_row .= $this->makeRowPart(str_replace(' ', '', $zip_code), 5);

      // Add city.
      $city = $address_paragraph?->get('field_city')->value ?? '';
      $student_row .= $this->makeRowPart($city, 20);

      // Add home municipality code (not applicable).
      $student_row .= $this->makeRowPart('', 4);

      // Add school municipality code.
      $student_row .= $this->makeRowPart(Settings::get('ssr_school_municipality_code'), 4);

      // Add email.
      $email = $use_contact_details ? $this->emailService->getUserEmail($student) : '';
      $student_row .= $this->makeRowPart($email, 70);

      $context['results']['extent_student_rows'][$student_uid] = $student_row;
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

  protected function makeRowPart(string|null $value, int $length, string $pad_type = 'right'): string {
    if (!$value) {
      $value = '';
    }
    $value = trim($value);
    $value = mb_substr($value, 0, $length);
    $value = mb_str_pad($value, $length, ' ', $pad_type === 'left' ? STR_PAD_LEFT : STR_PAD_RIGHT);
    return $value;
  }

  /**
   * @inheritDoc
   */
  public function beforeFinishExport(array $references, array &$context) {
    // Make the extens file.
    if (empty($context['results']['extent_student_rows'])) {
      return;
    }

    $file_content = 'MGBETYG';
    $student_row_prefix = $this->makeRowPart('B957T', 5);
    $student_row_prefix .= $this->makeRowPart(Settings::get('ssr_school_unit_code'), 8);
    $student_row_prefix .= $this->makeRowPart('', 2);
    $student_row_prefix .= $this->makeRowPart(Settings::get('ssr_school_name_short'), 3);

    foreach (array_values($context['results']['extent_student_rows']) as $delta => $student_row) {
      $row_id = mb_str_pad((string) ($delta + 1), 4, '0', STR_PAD_LEFT);
      $full_row = $row_id . $student_row_prefix . $student_row;
      if (mb_strlen($full_row) !== 274) {
        continue;
      }
      $file_content .= PHP_EOL . $full_row;
    }

    $file_name = 'extens_export_' . Settings::get('ssr_school_name') . '_' . $references['document_date']  .'.dat';
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

    // Convert to ANSI.
    $file_content = mb_convert_encoding($file_content, 'ISO-8859-1');

    file_put_contents($final_destination, $file_content);
  }

}
