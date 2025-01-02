<?php

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_entities\SsrMeetingInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the meeting entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_meeting",
 *   label = @Translation("Meeting"),
 *   label_collection = @Translation("Meetings"),
 *   label_singular = @Translation("meeting"),
 *   label_plural = @Translation("meetings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count meetings",
 *     plural = "@count meetings",
 *   ),
 *   bundle_label = @Translation("Meeting type"),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\simple_school_reports_entities\SsrMeetingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" =
 *   "Drupal\simple_school_reports_entities\SsrMeetingAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\SsrMeetingForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\SsrMeetingForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "ssr_meeting",
 *   data_table = "ssr_meeting_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr meeting types",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-meeting",
 *     "add-form" = "/meeting/add/{ssr_meeting_type}",
 *     "add-page" = "/meeting/add",
 *     "canonical" = "/meeting/{ssr_meeting}",
 *     "edit-form" = "/meeting/{ssr_meeting}/edit",
 *     "delete-form" = "/meeting/{ssr_meeting}/delete",
 *   },
 *   bundle_entity_type = "ssr_meeting_type",
 *   field_ui_base_route = "entity.ssr_meeting_type.edit_form",
 * )
 */
class SsrMeeting extends ContentEntityBase implements SsrMeetingInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  protected bool $keepListCache = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setKeepListCache(bool $value = TRUE): self {
    $this->keepListCache = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheTagsToInvalidate() {
    if (!$this->keepListCache) {
      return parent::getListCacheTagsToInvalidate();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the meeting was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the meeting was last edited.'));

    $fields['invited'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Invited'))
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['attending'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Attending'))
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Location'))
      ->setSetting('max_length', 1000)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['meeting_link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Meeting link'))
      ->setSetting('max_length', 3000)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['from'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('From'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('To'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['meta'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Meta data'))
      ->setSetting('max_length', 255)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['node_parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Node parent'))
      ->setSetting('target_type', 'node')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
