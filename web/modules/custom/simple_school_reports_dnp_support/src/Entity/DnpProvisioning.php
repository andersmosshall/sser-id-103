<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_dnp_support\DnpProvisioningInterface;
use Drupal\simple_school_reports_dnp_support\DnpProvisioningRowIdTrait;
use Drupal\simple_school_reports_dnp_support\DnpSourceDataInterface;
use Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType\DnpProvisioningFileLink;
use Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType\DnpProvisioningNumberClasses;
use Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType\DnpProvisioningNumberStaff;
use Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType\DnpProvisioningNumberStudents;
use Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType\DnpProvisioningNumberSubjectGroups;
use Drupal\simple_school_reports_dnp_support\Plugin\Field\FieldType\DnpProvisioningWarnings;
use Drupal\user\EntityOwnerTrait;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines the dnp provisioning entity class.
 *
 * @ContentEntityType(
 *   id = "dnp_provisioning",
 *   label = @Translation("Dnp Provisioning"),
 *   label_collection = @Translation("Dnp Provisionings"),
 *   label_singular = @Translation("dnp provisioning"),
 *   label_plural = @Translation("dnp provisionings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dnp provisionings",
 *     plural = "@count dnp provisionings",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_dnp_support\DnpProvisioningListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_dnp_support\DnpProvisioningAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_dnp_support\Form\DnpProvisioningForm",
 *       "edit" = "Drupal\simple_school_reports_dnp_support\Form\DnpProvisioningForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dnp_provisioning",
 *   data_table = "dnp_provisioning_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer dnp_provisioning",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/dnp-provisioning",
 *     "add-form" = "/dnp/provisioning/add",
 *     "canonical" = "/dnp/provisioning/{dnp_provisioning}",
 *     "edit-form" = "/dnp/provisioning/{dnp_provisioning}/edit",
 *     "delete-form" = "/dnp/provisioning/{dnp_provisioning}/delete",
 *     "delete-multiple-form" = "/admin/content/dnp-provisioning/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.dnp_provisioning.settings",
 * )
 */
final class DnpProvisioning extends ContentEntityBase implements DnpProvisioningInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use DnpProvisioningRowIdTrait;

  protected array|null $parsedSrc = null;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
    $this->set('label', $this->label());
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->generateFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function generateFileName(bool $process = FALSE): string {
    $file_name = '';
    if ($prefix = $this->get('file_name_prefix')->value) {
      $file_name = $prefix . ' ';
    }

    $file_name .= 'DNP';

    $created = $this->get('created')->value;
    if ($created) {
      $file_name .= ' ' . date('Y-m-d His', (int) $created);
    }

    if (!$process) {
      return $file_name;
    }

    $file_name = str_replace(' ', '_', $file_name);
    $file_name = str_replace('/', '-', $file_name);
    $file_name = str_replace('\\', '-', $file_name);
    $file_name = mb_strtolower($file_name);

    $event = new FileUploadSanitizeNameEvent($file_name, '');
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->dispatch($event);
    $file_name = $event->getFilename();

    return $file_name . '.xlsx';
  }

  /**
   * {@inheritdoc}
   */
  public function parseSrc(): array {
    if ($this->parsedSrc === null) {
      $src_json = $this->get('field_src')->value;
      if (empty($src_json)) {
        $this->parsedSrc = [];
      }
      else {
        $this->parsedSrc = json_decode($src_json, true);
      }
    }
    return $this->parsedSrc;
  }

  /**
   * @param string $sheet
   *
   * @return array
   */
  protected function getSheetData(string $sheet): array {
    $parsed_src = $this->parseSrc();
    $sheet_data = $parsed_src[$sheet] ?? [];

    foreach ($sheet_data as &$row) {
      foreach ($row as $property => &$value) {
        $value = trim((string) $value);

        // Name field has a restriction of 64 characters.
        $max_length_map = [
          'first_name' => 64,
          'middle_name' => 64,
          'last_name' => 64,
        ];
        if (isset($max_length_map[$property])) {
          $value = mb_substr($value, 0, $max_length_map[$property]);
        };
      }
    }

    return $sheet_data;
  }

  /**
   * {@inheritdoc}
   */
  public function createSrcData(DnpSourceDataInterface $src): DnpProvisioningInterface {
    $classes_data = [];
    $subject_groups_data = [];
    $students_data = [];
    $staff_data = [];
    $warnings = [];

    /** @var \Drupal\simple_school_reports_core\Service\TermServiceInterface $term_service */
    $term_service = \Drupal::service('simple_school_reports_core.term_service');
    $default_school_year = $term_service->getDefaultSchoolYearStart()->format('y') . $term_service->getDefaultSchoolYearEnd()->format('y');
    $school_unit_code = Settings::get('ssr_school_unit_code');

    if (!$school_unit_code) {
      throw new \RuntimeException('School unit code is not set.');
    }

    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    /** @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface $email_service */
    $email_service = \Drupal::service('simple_school_reports_core.email_service');

    /** @var \Drupal\simple_school_reports_core\Pnum $pnum_service */
    $pnum_service = \Drupal::service('simple_school_reports_core.pnum');

    $grades = $src->getGrades();
    $classes_to_remove = $src->getClassesToRemove();
    $class_ids_to_remove = array_keys($classes_to_remove);;
    foreach ($grades as $grade) {
      $id = $this->generateRowId(self::DNP_CLASSES_SHEET, $grade);
      $classes_data[$id] = [
        'id' => $id,
        'name' => $grade . '_' . $default_school_year,
        'school_form' => 'GR',
        'school_unit_code' => $school_unit_code,
        'remove' => in_array($id, $class_ids_to_remove) ? 'Ja' : '',
      ];
    }
    foreach ($classes_to_remove as $class_id => $row) {
      if (isset($classes_data[$class_id])) {
        continue;
      }
      $row['remove'] = 'Ja';
      $classes_data[$class_id] = $row;
    }

    $subject_groups_to_remove = $src->getSubjectGroupsToRemove();
    $subject_group_ids_to_remove = array_keys($subject_groups_to_remove);

    foreach ($grades as $grade) {
      $subject_codes = $src->getSubjectCodes($grade);
      foreach ($subject_codes as $subject_code) {
        $subject_code = strtoupper($subject_code);
        $id = $this->generateRowId(self::DNP_SUBJECT_GROUPS_SHEET, $grade . ':' . $subject_code);
        $class_id = $this->generateRowId(self::DNP_CLASSES_SHEET, $grade);
        $class_name = !empty($classes_data[$class_id]['name']) ? $classes_data[$class_id]['name'] : $grade;

        $test_activity_name = !empty(self::TEST_ACTIVITY_MAP[$grade][$subject_code])
          ? self::TEST_ACTIVITY_MAP[$grade][$subject_code]
          : NULL;

        if (!$test_activity_name) {
          \Drupal::logger('simple_school_reports_dnp_support')->error('Unsupported test activity, grade: ' . $grade . ' subject: ' . $subject_code);
          throw new \RuntimeException('Unsupported test activity, grade: ' . $grade . ' subject: ' . $subject_code);
        }

        $subject_groups_data[$id] = [
          'id' => $id,
          'name' => $class_name . '-' . $subject_code,
          'school_form' => 'GR',
          'test_activity_name' => $test_activity_name,
          'school_unit_code' => $school_unit_code,
          'remove' => in_array($id, $subject_group_ids_to_remove) ? 'Ja' : '',
        ];
      }
    }
    foreach ($subject_groups_to_remove as $subject_group_id => $row) {
      if (isset($subject_groups_data[$subject_group_id])) {
        continue;
      }
      $row['remove'] = 'Ja';
      $subject_groups_data[$subject_group_id] = $row;
    }

    $student_uids = $src->getStudentUids();
    $students_to_remove = $src->getStudentsToRemove();
    $student_ids_to_remove = array_keys($students_to_remove);
    foreach ($student_uids as $uid) {
      /** @var \Drupal\user\UserInterface $student */
      $student = $user_storage->load($uid);

      if (!$student) {
        \Drupal::logger('simple_school_reports_dnp_support')->error('User not found when building data (skipping row): ' . $uid);
        continue;
      }

      $grade = $student->get('field_grade')->value;
      $class_name = NULL;
      if ($grade) {
        $class_id = $this->generateRowId(self::DNP_CLASSES_SHEET, $grade);
        $class_name = !empty($classes_data[$class_id]['name']) ? $classes_data[$class_id]['name'] : NULL;
      }

      $id = $this->generateRowId(self::DNP_STUDENTS_SHEET, $uid);

      $dnp_username = $student->get('field_dnp_username')->value;
      if (!$dnp_username) {
        $dnp_username = calculate_dnp_username($student);
        $student->set('field_dnp_username', $dnp_username);
        $student->save();
      }

      $ssn = $student->get('field_ssn')->value ?? '';
      $ssn = $pnum_service->normalizeIfValid($ssn, TRUE);
      if (!$ssn) {
        $warnings[] = 'Ogiltigt personnummer för ' . $student->getDisplayName();
      }

      $email = '';
      if ($src->useEmailForStudent()) {
        $email = $email_service->getUserEmail($student) ?? '';
      }

      $subject_groups = [];
      $subject_codes = $src->getSubjectCodesForStudent($uid);
      foreach ($subject_codes as $subject_code) {
        $subject_code = strtoupper($subject_code);
        $subject_group_id = $this->generateRowId(self::DNP_SUBJECT_GROUPS_SHEET, $grade . ':' . $subject_code);
        $subject_group_name = !empty($subject_groups_data[$subject_group_id]['name']) ? $subject_groups_data[$subject_group_id]['name'] : NULL;
        if ($subject_group_name) {
          $subject_groups[] = $subject_group_name;
        }
      }

      $students_data[$id] = [
        'id' => $id,
        'username' => $dnp_username,
        'ssn' => $ssn ?? '',
        'first_name' => $student->get('field_first_name')->value ?? '',
        'middle_name' => $student->get('field_middle_name')->value ?? '',
        'last_name' => $student->get('field_last_name')->value ?? '',
        'email' => $email,
        'school_form' => 'GR',
        'school_unit_code' => $school_unit_code,
        'study_path_code' => '',
        'subject_groups' => implode(', ', $subject_groups),
        'remove' => in_array($id, $student_ids_to_remove) ? 'Ja' : '',
      ];

      if ($src->useSecrecyMarking($uid)) {
        $students_data[$id]['secrecy_marking'] = 'Sekretessmarkering';
      }
      if ($grade) {
        $students_data[$id]['grade'] = $grade;
      }
      if ($class_name) {
        $students_data[$id]['class'] = $class_name;
      }
    }
    foreach ($students_to_remove as $student_id => $row) {
      if (isset($students_data[$student_id])) {
        continue;
      }
      $row['remove'] = 'Ja';
      $students_data[$student_id] = $row;
    }

    $staff_uids = $src->getStaffUids();
    $staff_to_remove = $src->getStaffToRemove();
    $staff_ids_to_remove = array_keys($students_to_remove);

    foreach ($staff_uids as $uid) {
      /** @var \Drupal\user\UserInterface $staff */
      $staff = $user_storage->load($uid);

      if (!$staff) {
        \Drupal::logger('simple_school_reports_dnp_support')->error('User not found when building data (skipping row): ' . $uid);
        continue;
      }

      $id = $this->generateRowId(self::DNP_STAFF_SHEET, $uid);

      $dnp_username = $staff->get('field_dnp_username')->value;
      if (!$dnp_username) {
        $dnp_username = calculate_dnp_username($staff);
        $staff->set('field_dnp_username', $dnp_username);
        $staff->save();
      }

      $ssn = $staff->get('field_ssn')->value ?? '';
      $ssn = $pnum_service->normalizeIfValid($ssn, TRUE);
      if (!$ssn) {
        $warnings[] = 'Ogiltigt personnummer för ' . $staff->getDisplayName();
      }

      $secrecy_marking = NULL;

      $email = '';
      if ($src->useEmailForStaff()) {
        $email = $email_service->getUserEmail($staff) ?? '';
      }

      $staff_category = 'Annan personal';
      if ($staff->hasRole('principle')) {
        $staff_category = 'Rektor';
      }
      elseif ($staff->hasRole('administrator')) {
        $staff_category = 'Skoladministratör';
      }
      elseif ($staff->hasRole('teacher')) {
        $staff_category = 'Lärare';
      }

      $subject_groups = [];
      foreach ($grades as $grade) {
        $subject_codes = $src->getSubjectCodesForStaff($uid, $grade);
        foreach ($subject_codes as $subject_code) {
          $subject_code = strtoupper($subject_code);
          $subject_group_id = $this->generateRowId(self::DNP_SUBJECT_GROUPS_SHEET, $grade . ':' . $subject_code);
          $subject_group_name = !empty($subject_groups_data[$subject_group_id]['name']) ? $subject_groups_data[$subject_group_id]['name'] : NULL;
          if ($subject_group_name) {
            $subject_groups[] = $subject_group_name;
          }
        }
      }

      $staff_data[$id] = [
        'staff_id' => 's' . $id,
        'id' => $id,
        'username' => $dnp_username,
        'eduid_username' => '',
        'ssn' => $ssn ?? '',
        'first_name' => $staff->get('field_first_name')->value ?? '',
        'middle_name' => $student->get('field_middle_name')->value ?? '',
        'last_name' => $staff->get('field_last_name')->value ?? '',
        'email' => $email,
        'school_unit_code' => $school_unit_code,
        'staff_category' => $staff_category,
        'subject_groups' => implode(', ', $subject_groups),
        'remove' => in_array($id, $staff_ids_to_remove) ? 'Ja' : '',
      ];

      if ($secrecy_marking) {
        $staff_data[$id]['secrecy_marking'] = $secrecy_marking;
      }
    }

    foreach ($staff_to_remove as $staff_id => $row) {
      if (isset($staff_data[$staff_id])) {
        continue;
      }
      $row['remove'] = 'Ja';
      $staff_data[$staff_id] = $row;
    }

    $this->parsedSrc = [
      self::DNP_CLASSES_SHEET => $classes_data,
      self::DNP_SUBJECT_GROUPS_SHEET => $subject_groups_data,
      self::DNP_STUDENTS_SHEET => $students_data,
      self::DNP_STAFF_SHEET => $staff_data,
      '_warnings' => $warnings,
      '_key' => \Drupal::service('uuid')->generate(),
    ];
    $this->set('field_src', json_encode($this->parsedSrc));
    $this->set('settings', json_encode($src->toArray()));
    $this->set('file_name_prefix', $src->get('file_name_prefix')->value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableRenderArray(string $sheet): array {

    $headers = self::HEADERS_MAP[$sheet] ?? [];
    $data = $this->getSheetData($sheet);

    foreach ($data as $item) {
      $row = [];
      foreach ($headers as $key => $header) {
        $row[$key] = $item[$key] ?? '';
      }
      $rows[] = $row;
    }

    return [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No data available'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFileMapData(string $sheet): array {
    $data = $this->getSheetData($sheet);

    $cell_sheet_map = self::CELL_SHEET_MAP[$sheet] ?? [];
    $map = [];
    $row_id = $cell_sheet_map['first_row'] ?? 0;
    foreach ($data as $row) {
      foreach ($row as $property => $value) {
        if (!isset($cell_sheet_map[$property])) {
          continue;
        }
        $cell_id = $cell_sheet_map[$property] . $row_id;
        $map[$cell_id] = $value;
      }
      $row_id++;
    }

    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(string $sheet, int $option = self::DNP_IDS_ONLY_IMPORTS): array {
    $data = $this->getSheetData($sheet);
    $ids = [];

    foreach ($data as $row) {
      $remove = !empty($row['remove']);
      if ($option === self::DNP_IDS_ONLY_IMPORTS && $remove) {
        continue;
      }
      if ($option === self::DNP_IDS_ONLY_REMOVE && !$remove) {
        continue;
      }
      $ids[] = $row['id'];
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getUids(string $sheet, int $option = self::DNP_IDS_ONLY_IMPORTS): array {
    $uids_sheets = [
      self::DNP_STUDENTS_SHEET,
      self::DNP_STAFF_SHEET,
    ];
    if (!in_array($sheet, $uids_sheets)) {
      return [];
    }
    $row_ids = $this->getIds($sheet, $option);
    $uids = [];
    foreach ($row_ids as $row_id) {
      $uids[] = $this->getItemIdFromGeneratedRowId($row_id);
    }
    return $uids;
  }

  /**
   * {@inheritdoc}
   */
  public function getRowCount(string $sheet, int $option = self::DNP_IDS_ONLY_IMPORTS): int {
    return count($this->getIds($sheet, $option));
  }

  /**
   * {@inheritdoc}
   */
  public function getWarnings(): array {
    $parsed_src = $this->parseSrc();
    $warnings = $parsed_src['_warnings'] ?? [];
    return array_values($warnings);
  }

  public function makeXlsxFile(): Spreadsheet {
    /** @var \Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');

    $template_file = 'dnp_empty';
    $empty_file = $file_template_service->getFileTemplateRealPath($template_file);
    if (!$empty_file) {
      throw new NotFoundHttpException();
    }
    $file_type = 'Xlsx';
    $reader = IOFactory::createReader($file_type);
    $reader->setLoadAllSheets();
    $spreadsheet = $reader->load($empty_file);

    $sheet_names = $spreadsheet->getSheetNames();
    foreach ($sheet_names as $sheet_index => $sheet_name) {
      $sheet = self::FILE_SHEET_MAP[$sheet_index] ?? NULL;
      if (!$sheet) {
        continue;
      }
      $excel_sheet = $spreadsheet->getSheet($sheet_index);
      $data = $this->getFileMapData($sheet);
      foreach ($data as $cell_id => $value) {
        $excel_sheet->setCellValue($cell_id, $value);
      }
    }
    return $spreadsheet;
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['synced'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Synced'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('off_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['downloaded'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Downloaded'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('off_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Created by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the dnp provisioning was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the dnp provisioning was last edited.'));

    $fields['file_name_prefix'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('File name prefix'))
      ->setDescription(t('The prefix to use for the provisioning file name.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Json encoded settings'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('File for DNP Provisioning'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass(DnpProvisioningFileLink::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['number_of_classes'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of classes'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass(DnpProvisioningNumberClasses::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['number_of_subject_groups'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of subject groups'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass(DnpProvisioningNumberSubjectGroups::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['number_of_students'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of students'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass(DnpProvisioningNumberStudents::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['number_of_staff'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of staff members'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass(DnpProvisioningNumberStaff::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['warnings'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Warnings'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass(DnpProvisioningWarnings::class)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
