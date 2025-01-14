<?php

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simple_school_reports_entities\SsrConsentAnswerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the consent answer entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_consent_answer",
 *   label = @Translation("Consent answer"),
 *   label_collection = @Translation("Consent answers"),
 *   label_singular = @Translation("consent answer"),
 *   label_plural = @Translation("consent answers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count consent answers",
 *     plural = "@count consent answers",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_entities\SsrConsentAnswerListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_entities\SsrConsentAnswerAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\SsrConsentAnswerForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\SsrConsentAnswerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "ssr_consent_answer",
 *   revision_table = "ssr_consent_answer_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer ssr consent answer",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-consent-answer",
 *     "add-form" = "/ssr-consent-answer/add",
 *     "canonical" = "/ssr-consent-answer/{ssr_consent_answer}",
 *     "edit-form" = "/ssr-consent-answer/{ssr_consent_answer}/edit",
 *     "delete-form" = "/ssr-consent-answer/{ssr_consent_answer}/delete",
 *   },
 *   field_ui_base_route = "entity.ssr_consent_answer.settings",
 * )
 */
class SsrConsentAnswer extends RevisionableContentEntityBase implements SsrConsentAnswerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }

    if (!$this->isNew()) {
      $current_uid = \Drupal::currentUser()->id();
      $this->setOwnerId($current_uid);
      if (!$this->isSyncing()) {
        $this->setNewRevision();
        $this->setRevisionLogMessage('');
        $this->setRevisionCreationTime(\Drupal::time()->getCurrentTime());
        $this->setRevisionUserId($current_uid);
      }
      else {
        $this->setNewRevision(FALSE);
      }
    }

    if (!$this->isSyncing()) {
      $answer_value = $this->get('answer')->value;
      $answer_options = simple_school_reports_entities_answer_types();
      $answer = $answer_options[$answer_value];

      $changed = new \DateTime();
      $changed->setTimestamp($this->get('created')->value);

      $label = $answer . ' (' . $changed->format('Y-m-d') . ')';
      if ($this->getOwnerId() != $this->get('handler_uid')->target_id) {
        $handler = $this->get('handler_uid')->entity;
        $handler_name = $handler?->getDisplayName() ?? 'Okänd';
        $label .= ' (Hanterad av ' . $handler_name . ')';
      }

      $label = (strlen($label) > 500) ? substr($label,0,500).'...' : $label;
      $this->set('label', $label);
    }
  }

  public function getListCacheTagsToInvalidate() {
    $tags = [];

    if ($consent_id = $this->get('consent')->target_id) {
      $tags[] = 'ssr_consent_answer_list:' . $consent_id;
      if ($target_uid = $this->get('target_uid')->target_id) {
        $tags[] = 'ssr_consent_answer_list:' . $consent_id . ':' . $target_uid;
      }
    }

    return Cache::mergeTags(parent::getListCacheTagsToInvalidate(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
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
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('Owner'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the consent answer was last edited.'));

    $fields['consent'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('Consent'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['consent' => 'consent']])
      ->setReadOnly(TRUE);

    $fields['target_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('Consent target'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setReadOnly(TRUE);

    $fields['answer'] = BaseFieldDefinition::create('list_integer')
      ->setRevisionable(TRUE)
      ->setLabel(t('Answer'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_answer_types')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['signing'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setLabel(t('Signing'))
      ->setSetting('target_type', 'ssr_signing')
      ->setReadOnly(TRUE);

    $fields['handler_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setLabel(t('Handled by'))
      ->setSetting('target_type', 'user')
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

    return $fields;
  }

}
