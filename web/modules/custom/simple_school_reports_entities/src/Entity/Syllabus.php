<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_entities\Plugin\Field\FieldType\SyllabusLevels;
use Drupal\simple_school_reports_entities\SyllabusInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the syllabus entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_syllabus",
 *   label = @Translation("Syllabus"),
 *   label_collection = @Translation("Syllabi"),
 *   label_singular = @Translation("syllabus"),
 *   label_plural = @Translation("syllabi"),
 *   label_count = @PluralTranslation(
 *     singular = "@count syllabi",
 *     plural = "@count syllabi",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_entities\SyllabusListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_entities\SyllabusAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\SyllabusForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\SyllabusForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_syllabus",
 *   data_table = "ssr_syllabus_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_syllabus",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-syllabus",
 *     "add-form" = "/ssr-syllabus/add",
 *     "canonical" = "/ssr-syllabus/{ssr_syllabus}",
 *     "edit-form" = "/ssr-syllabus/{ssr_syllabus}/edit",
 *     "delete-form" = "/ssr-syllabus/{ssr_syllabus}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-syllabus/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_syllabus.settings",
 * )
 */
final class Syllabus extends ContentEntityBase implements SyllabusInterface {

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

    if ($this->get('school_subject')->isEmpty()) {
      throw new \LogicException('The school subject id cannot be empty.');
    }

    if ($this->get('identifier')->isEmpty()) {
      throw new \LogicException('Identifier cannot be empty.');
    }

    // Official syllabi cannot be custom.
    if ($this->get('official')->value) {
      $this->set('custom', FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['short_label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 12)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Identifier'))
      ->addConstraint('UniqueField')
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setTranslatable(TRUE)
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the syllabus was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setTranslatable(TRUE)
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the syllabus was last edited.'));

    $fields['school_subject'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Subject'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['school_subject' => 'school_subject'],
        'auto_create' => FALSE,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['group_for'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group for'))
      ->setRequired(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'ssr_syllabus')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_type_versioned'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Lookup type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_core_school_type_versioned_options')
      ->setDefaultValue('default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // A comma separated list of course identifiers that in a set of syllabus
    // levels.
    $fields['levels'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Levels codes'));

    $fields['levels_display'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Levels'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setClass(SyllabusLevels::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject code'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 12)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject code'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject_designation'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject code'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['course_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject code'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['language_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject code'))
      ->setSetting('max_length', 5)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['points'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Points'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['specialisation_label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Specialisation label'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['specialisation_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Specialisation description'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['official'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Official'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['custom'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('School custom'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['grade_vid'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Grade system'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_grade_vid_options')
      ->setDefaultValue('default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
