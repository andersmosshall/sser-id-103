<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_class_support\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simple_school_reports_class_support\Plugin\Field\FieldType\SchoolClassCountStudents;
use Drupal\simple_school_reports_class_support\Plugin\Field\FieldType\SchoolClassMentors;
use Drupal\simple_school_reports_class_support\Plugin\Field\FieldType\SchoolClassStudents;
use Drupal\simple_school_reports_class_support\SchoolClassInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the school class entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_school_class",
 *   label = @Translation("School class"),
 *   label_collection = @Translation("School classes"),
 *   label_singular = @Translation("school class"),
 *   label_plural = @Translation("school classes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count school classes",
 *     plural = "@count school classes",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_class_support\SchoolClassListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_class_support\SchoolClassAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_class_support\Form\SchoolClassForm",
 *       "edit" = "Drupal\simple_school_reports_class_support\Form\SchoolClassForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_school_class",
 *   data_table = "ssr_school_class_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_school_class",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-school-class",
 *     "add-form" = "/class/add",
 *     "canonical" = "/class/{ssr_school_class}",
 *     "edit-form" = "/class/{ssr_school_class}/edit",
 *     "delete-form" = "/class/{ssr_school_class}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-school-class/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_school_class.settings",
 * )
 */
final class SchoolClass extends ContentEntityBase implements SchoolClassInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }

    $student_suffix_changed = FALSE;
    if (!$this->isNew() && $this->original) {
      $student_suffix_changed = $this->get('student_suffix')->value !== $this->original->get('student_suffix')->value;
    }

    if ($student_suffix_changed) {
      $student_uids = array_column($this->get('students')->getValue(), 'target_id');
      if (!empty($student_uids)) {
        $tags = [];
        foreach ($student_uids as $uid) {
          $tags[] = 'user:' . $uid;
        }
        Cache::invalidateTags($tags);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    if (isset($fields[$entity_type->getKey('langcode')])) {
      $fields[$entity_type->getKey('langcode')]->setDefaultValue('sv');
    }

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->addConstraint('UniqueField')
      ->setLabel(t('Name'))
      ->setDescription(t('Use as short name as possible!'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nickname'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Nickname'))
      ->setDescription(t('Optionally set a nickname for the class.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_week'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('School week'))
      ->setSetting('target_type', 'school_week')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Created by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this term in relation to other terms.'))
      ->setDefaultValue(0);

    $fields['student_suffix'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Behavior of student suffix'))
      ->setDescription(t('Choose what suffix students have in different lists for identification. Class name will be the name of this class and grade will be the grade of the student.'))
      ->setRequired(TRUE)
      ->setDefaultValue('class')
      ->setSetting('allowed_values_function', 'simple_school_reports_dnp_support_student_suffix_options')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['students'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Students'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setClass(SchoolClassStudents::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mentors'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Mentors'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setClass(SchoolClassMentors::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['number_of_students'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of students'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setClass(SchoolClassCountStudents::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the school class was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the school class was last edited.'));

    return $fields;
  }

}
