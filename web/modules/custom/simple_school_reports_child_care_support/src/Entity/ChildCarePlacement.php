<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_child_care_support\ChildCarePlacementAccessControlHandler;
use Drupal\simple_school_reports_child_care_support\ChildCarePlacementInterface;
use Drupal\simple_school_reports_child_care_support\ChildCarePlacementListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCarePlacementForm;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the child care placement entity class.
 */
#[ContentEntityType(
  id: 'ssr_child_care_placement',
  label: new TranslatableMarkup('Child care placement'),
  label_collection: new TranslatableMarkup('Child care placements'),
  label_singular: new TranslatableMarkup('child care placement'),
  label_plural: new TranslatableMarkup('child care placements'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCarePlacementListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ChildCarePlacementAccessControlHandler::class,
    'form' => [
      'add' => ChildCarePlacementForm::class,
      'edit' => ChildCarePlacementForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-child-care-placement',
    'add-form' => '/ssr-child-care-placement/add',
    'canonical' => '/ssr-child-care-placement/{ssr_child_care_placement}',
    'edit-form' => '/ssr-child-care-placement/{ssr_child_care_placement}/edit',
    'delete-form' => '/ssr-child-care-placement/{ssr_child_care_placement}/delete',
    'delete-multiple-form' => '/admin/content/ssr-child-care-placement/delete-multiple',
  ],
  admin_permission: 'administer ssr_child_care_placement',
  base_table: 'ssr_child_care_placement',
  label_count: [
    'singular' => '@count child care placements',
    'plural' => '@count child care placements',
  ],
  field_ui_base_route: 'entity.ssr_child_care_placement.settings',
)]
class ChildCarePlacement extends ContentEntityBase implements ChildCarePlacementInterface {

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
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
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
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the child care placement was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the child care placement was last edited.'));

    return $fields;
  }

}
