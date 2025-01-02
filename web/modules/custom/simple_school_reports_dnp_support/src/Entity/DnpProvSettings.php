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
use Drupal\simple_school_reports_dnp_support\DnpProvisioningRowIdTrait;
use Drupal\simple_school_reports_dnp_support\DnpProvSettingsInterface;
use Drupal\simple_school_reports_dnp_support\Service\DnpSupportServiceInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the dnp provisioning settings entity class.
 *
 * @ContentEntityType(
 *   id = "dnp_prov_settings",
 *   label = @Translation("Dnp Provisioning Settings"),
 *   label_collection = @Translation("Dnp Provisioning Settings"),
 *   label_singular = @Translation("dnp provisioning settings"),
 *   label_plural = @Translation("dnp provisioning settings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dnp provisioning settingss",
 *     plural = "@count dnp provisioning settingss",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_dnp_support\DnpProvSettingsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_dnp_support\DnpProvSettingsAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_dnp_support\Form\DnpProvSettingsForm",
 *       "edit" = "Drupal\simple_school_reports_dnp_support\Form\DnpProvSettingsForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dnp_prov_settings",
 *   data_table = "dnp_prov_settings_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer dnp_prov_settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/dnp-prov-settings",
 *     "add-form" = "/dnp/provisioning-settings/add",
 *     "canonical" = "/dnp/provisioning-settings/{dnp_prov_settings}",
 *     "edit-form" = "/dnp/provisioning-settings/{dnp_prov_settings}/edit",
 *     "delete-form" = "/dnp/provisioning-settings/{dnp_prov_settings}/delete",
 *     "delete-multiple-form" = "/admin/content/dnp-prov-settings/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.dnp_prov_settings.settings",
 * )
 */
final class DnpProvSettings extends ContentEntityBase implements DnpProvSettingsInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use DnpProvisioningRowIdTrait;

  protected ?DnpSupportServiceInterface $dnpSupportService = NULL;

  protected array $lookup = [];

  protected array $lastDnpProvisioningParsed = [];

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
    $this->set('label', 'DNP Inställningar för provisionering');
  }

  protected function dnpSupportService(): DnpSupportServiceInterface {
    if (!$this->dnpSupportService) {
      $this->dnpSupportService = \Drupal::service('simple_school_reports_dnp_support.dnp_support_service');
    }
    return $this->dnpSupportService;
  }

  public function getGrades(): array {
    $cid = 'grades';
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }
    $grades = [];

    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvTestSettingsInterface $test */
    foreach ($this->get('tests')->referencedEntities() as $test) {
      $test_id = $test->get('test')->value;
      $grade = $this->dnpSupportService()->getGradeFromDnpTestOption($test_id);
      if ($grade) {
        $grades[$grade] = $grade;
      }
    }

    $grades = array_values($grades);
    sort($grades);
    $this->lookup[$cid] = $grades;
    return $grades;
  }

  public function getClassesToRemove(): array {
    if (empty($this->lastDnpProvisioningParsed[self::DNP_CLASSES_SHEET])) {
      return [];
    }

    $new_classes = [];
    foreach ($this->getGrades() as $grade) {
      $id = $this->generateRowId(self::DNP_CLASSES_SHEET, $grade);
      $new_classes[$id] = $id;
    }

    $classes_to_remove = [];
    foreach ($this->lastDnpProvisioningParsed[self::DNP_CLASSES_SHEET] as $class_row) {
      if ($class_row['remove'] === 'Ja') {
        continue;
      }
      $id = $class_row['id'] ?? NULL;
      if (!array_key_exists($id, $new_classes)) {
        $classes_to_remove[] = $class_row;
      }
    }

    return $classes_to_remove;
  }

  public function getSubjectCodes(string $grade): array {
    $cid = 'subject_codes_' . $grade;
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }
    $subject_codes = [];

    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvTestSettingsInterface $test */
    foreach ($this->get('tests')->referencedEntities() as $test) {
      $test_id = $test->get('test')->value;

      $test_grade = $this->dnpSupportService()->getGradeFromDnpTestOption($test_id);
      if ($test_grade !== $grade) {
        continue;
      }

      $subject_code = $this->dnpSupportService()->getSubjectFromDnpTestOption($test_id);
      if ($subject_code) {
        $subject_codes[$subject_code] = $subject_code;
      }
    }

    $subject_codes = array_values($subject_codes);
    sort($subject_codes);
    $this->lookup[$cid] = $subject_codes;
    return $subject_codes;
  }

  public function getSubjectGroupsToRemove(): array {
    if (empty($this->lastDnpProvisioningParsed[self::DNP_SUBJECT_GROUPS_SHEET])) {
      return [];
    }

    $new_subject_groups = [];
    foreach ($this->getGrades() as $grade) {
      foreach ($this->getSubjectCodes($grade) as $subject_code) {
        $id = $this->generateRowId(self::DNP_SUBJECT_GROUPS_SHEET, $grade . ':' . $subject_code);
        $new_subject_groups[$id] = $id;
      }
    }

    $subject_groups_to_remove = [];
    foreach ($this->lastDnpProvisioningParsed[self::DNP_SUBJECT_GROUPS_SHEET] as $subject_group_row) {
      if ($subject_group_row['remove'] === 'Ja') {
        continue;
      }
      $id = $subject_group_row['id'] ?? NULL;
      if (!array_key_exists($id, $new_subject_groups)) {
        $subject_groups_to_remove[] = $subject_group_row;
      }
    }

    return $subject_groups_to_remove;
  }

  public function getStudentUids(): array {
    $cid = 'student_uids';
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }
    $total_students = [];

    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvTestSettingsInterface $test */
    foreach ($this->get('tests')->referencedEntities() as $test) {
      $test_id = $test->get('test')->value;

      $all_test_students = $this->dnpSupportService()->getStudentUidsForTest($test_id);
      $field_students = array_column($test->get('students')->getValue(), 'target_id');

      $list_behaviour = (int) $test->get('list_behavior')->value;

      if ($list_behaviour === self::DNP_LIST_INCLUDE) {
        $students = array_intersect($all_test_students, $field_students);
      }
      else {
        $students = array_diff($all_test_students, $field_students);
      }

      foreach ($students as $student) {
        if (empty($this->lookup['tests_' . $student])) {
          $this->lookup['tests_' . $student] = [];
        }
        $this->lookup['tests_' . $student][$test_id] = $test_id;
      }

      $total_students = array_merge($total_students, $students);
    }


    $ordered_student_uids = [];

    if (!empty($total_students)) {
      $ordered_student_uids = \Drupal::entityTypeManager()->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', array_unique($total_students), 'IN')
        ->sort('field_grade')
        ->sort('field_first_name')
        ->sort('field_last_name')
        ->execute();
      $ordered_student_uids = array_values($ordered_student_uids);
    }

    $this->lookup[$cid] = $ordered_student_uids;
    return $ordered_student_uids;
  }

  public function getSubjectCodesForStudent(int|string $student_uid): array {
    // Warm up the cache.
    $this->getStudentUids();

    $subject_codes = [];

    foreach ($this->lookup['tests_' . $student_uid] ?? [] as $test_id) {
      $subject_code = $this->dnpSupportService()->getSubjectFromDnpTestOption($test_id);
      if ($subject_code) {
        $subject_codes[$subject_code] = $subject_code;
      }
    }

    $subject_codes = array_values($subject_codes);
    sort($subject_codes);
    return $subject_codes;
  }

  public function getStudentsToRemove(): array {
    if (empty($this->lastDnpProvisioningParsed[self::DNP_STUDENTS_SHEET])) {
      return [];
    }

    $new_students = [];
    foreach ($this->getStudentUids() as $student_uid) {
      $id = $this->generateRowId(self::DNP_STUDENTS_SHEET, $student_uid);
      $new_students[$id] = $id;
    }

    $students_to_remove = [];
    foreach ($this->lastDnpProvisioningParsed[self::DNP_STUDENTS_SHEET] as $student_row) {
      if ($student_row['remove'] === 'Ja') {
        continue;
      }
      $id = $student_row['id'] ?? NULL;
      if (!array_key_exists($id, $new_students)) {
        $students_to_remove[] = $student_row;
      }
    }

    return $students_to_remove;
  }

  public function useSecrecyMarking(int|string $uid): bool {
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
    $protected_data_value = $user?->get('field_protected_personal_data')->value ?? NULL;
    return $protected_data_value !== NULL && $protected_data_value !== 'none';
  }

  public function useEmailForStudent(): bool {
    return $this->get('include_student_email')->value === '1' || $this->get('include_student_email')->value === TRUE;
  }

  public function getStaffUids(): array {
    $cid = 'staff_uids';
    if (array_key_exists($cid, $this->lookup)) {
      return $this->lookup[$cid];
    }
    $total_teachers = array_values(array_column($this->get('guaranteed_staff')->getValue(), 'target_id'));

    /** @var \Drupal\simple_school_reports_dnp_support\DnpProvTestSettingsInterface $test */
    foreach ($this->get('tests')->referencedEntities() as $test) {
      $test_id = $test->get('test')->value;

      $teachers = array_column($test->get('teachers')->getValue(), 'target_id');

      foreach ($teachers as $teacher) {
        if (empty($this->lookup['tests_' . $teacher])) {
          $this->lookup['tests_' . $teacher] = [];
        }
        $this->lookup['tests_' . $teacher][$test_id] = $test_id;
      }

      $total_teachers = array_merge($total_teachers, $teachers);
    }

    $ordered_teacher_uids = [];
    if (!empty($total_teachers)) {
      $ordered_teacher_uids = \Drupal::entityTypeManager()->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', array_unique($total_teachers), 'IN')
        ->sort('field_first_name')
        ->sort('field_last_name')
        ->execute();
      $ordered_teacher_uids = array_values($ordered_teacher_uids);
    }
    $this->lookup[$cid] = $ordered_teacher_uids;
    return $ordered_teacher_uids;
  }

  public function getSubjectCodesForStaff(int|string $staff_uid, string $grade): array {
    // Warm up the cache.
    $this->getStaffUids();

    $subject_codes = [];

    foreach ($this->lookup['tests_' . $staff_uid] ?? [] as $test_id) {
      $test_grade = $this->dnpSupportService()->getGradeFromDnpTestOption($test_id);
      if ($test_grade !== $grade) {
        continue;
      }
      $subject_code = $this->dnpSupportService()->getSubjectFromDnpTestOption($test_id);
      if ($subject_code) {
        $subject_codes[$subject_code] = $subject_code;
      }
    }

    $subject_codes = array_values($subject_codes);
    sort($subject_codes);
    return $subject_codes;

  }

  public function getStaffToRemove(): array {
    if (empty($this->lastDnpProvisioningParsed[self::DNP_STAFF_SHEET])) {
      return [];
    }

    $new_staff = [];
    foreach ($this->getStaffUids() as $staff_uid) {
      $id = $this->generateRowId(self::DNP_STAFF_SHEET, $staff_uid);
      $new_staff[$id] = $id;
    }

    $staff_to_remove = [];
    foreach ($this->lastDnpProvisioningParsed[self::DNP_STAFF_SHEET] as $staff_row) {
      if ($staff_row['remove'] === 'Ja') {
        continue;
      }
      $id = $staff_row['id'] ?? NULL;
      if (!array_key_exists($id, $new_staff)) {
        $staff_to_remove[] = $staff_row;
      }
    }

    return $staff_to_remove;
  }

  /**
   * {@inheritdoc}
   */
  public function useEmailForStaff(): bool {
    return $this->get('include_staff_email')->value === '1' || $this->get('include_staff_email')->value === TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastProvisioningData(array $parsed_data): self {
    $this->lastDnpProvisioningParsed = $parsed_data;
    return $this;
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

    $fields['file_name_prefix'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('File name prefix'))
      ->setDescription(t('The prefix to use for the provisioning file name.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['include_staff_email'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Include staff email'))
      ->setDescription(t('Include email in the provisioning file if user has a valid email.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('off_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['include_student_email'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Include student email'))
      ->setDescription(t('Include email in the provisioning file if user has a valid email.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('off_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['guaranteed_staff'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Guaranteed staff'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDescription(t('Staff that will be exported regardless of if they occur as teachers in affected classes or not. For example principles or administrators.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler_settings', [
        'filter' => [
          'type' => 'role',
          'role' => [
            'teacher' => 'teacher',
            'administrator' => 'administrator',
            'principle' => 'principle',
          ],
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tests'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tests', [], ['context' => 'assessment']))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'dnp_prov_test_settings')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['secrecy_marking'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Users with secrecy marking (DEPRECATED DO NOT USE)'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDescription(t('Users that should be marked with \"Sekretessmarkering\" to Skolverkets test portal. In general such secret data should not be in SSR but if there are user that still should have this marking to Skolverket, add those here. Otherwise you can add secrete user data manually in the generated file before uploading to Skolverket.'))
      ->setSetting('target_type', 'user')
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
      ->setDescription(t('The time that the dnp provisioning settings was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the dnp provisioning settings was last edited.'));

    return $fields;
  }

}
