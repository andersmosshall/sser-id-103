<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\link\LinkItemInterface;
use Drupal\simple_school_reports_entities\Plugin\Field\FieldType\ProgrammeStudents;
use Drupal\simple_school_reports_entities\ProgrammeInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the programme entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_programme",
 *   label = @Translation("Programme"),
 *   label_collection = @Translation("Programmes"),
 *   label_singular = @Translation("programme"),
 *   label_plural = @Translation("programmes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count programmes",
 *     plural = "@count programmes",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_entities\ProgrammeListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_entities\ProgrammeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\ProgrammeForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\ProgrammeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_programme",
 *   data_table = "ssr_programme_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_programme",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-programme",
 *     "add-form" = "/ssr-programme/add",
 *     "canonical" = "/ssr-programme/{ssr_programme}",
 *     "edit-form" = "/ssr-programme/{ssr_programme}/edit",
 *     "delete-form" = "/ssr-programme/{ssr_programme}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-programme/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_programme.settings",
 * )
 */
final class Programme extends ContentEntityBase implements ProgrammeInterface {

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
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the programme was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the programme was last edited.'));

    $fields['school_type_versioned'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('School type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_core_school_type_versioned_options')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setSetting('target_type', 'ssr_programme')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Programme type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_programme_type_options')
      ->setDefaultValue('programme')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Code'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['students'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Students'))
      ->setComputed(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setClass(ProgrammeStudents::class)
      ->setDisplayConfigurable('view', TRUE);

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link'))
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_OPTIONAL,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
