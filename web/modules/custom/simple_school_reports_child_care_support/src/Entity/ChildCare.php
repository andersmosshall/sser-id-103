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
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_child_care_support\ChildCareAccessControlHandler;
use Drupal\simple_school_reports_child_care_support\ChildCareInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareForm;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the child care entity class.
 */
#[ContentEntityType(
  id: 'ssr_child_care',
  label: new TranslatableMarkup('Child care'),
  label_collection: new TranslatableMarkup('Child cares'),
  label_singular: new TranslatableMarkup('child care'),
  label_plural: new TranslatableMarkup('child cares'),
  entity_keys: [
    'id' => 'id',
    'langcode' => 'langcode',
    'bundle' => 'bundle',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCareListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ChildCareAccessControlHandler::class,
    'form' => [
      'add' => ChildCareForm::class,
      'edit' => ChildCareForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-child-care',
    'add-form' => '/ssr-child-care/add/{ssr_child_care_type}',
    'add-page' => '/ssr-child-care/add',
    'canonical' => '/ssr-child-care/{ssr_child_care}',
    'edit-form' => '/ssr-child-care/{ssr_child_care}/edit',
    'delete-form' => '/ssr-child-care/{ssr_child_care}/delete',
    'delete-multiple-form' => '/admin/content/ssr-child-care/delete-multiple',
  ],
  admin_permission: 'administer ssr_child_care types',
  bundle_entity_type: 'ssr_child_care_type',
  bundle_label: new TranslatableMarkup('Child care type'),
  base_table: 'ssr_child_care',
  data_table: 'ssr_child_care_field_data',
  translatable: TRUE,
  label_count: [
    'singular' => '@count child cares',
    'plural' => '@count child cares',
  ],
  field_ui_base_route: 'entity.ssr_child_care_type.edit_form',
)]
class ChildCare extends ContentEntityBase implements ChildCareInterface {

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
  public function getShortLabel(): string {
    return $this->get('short_name')->value ?? '-';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['short_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Short name'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDescription(t('Short label, recommended 3 characters.'))
      ->setSetting('max_length', 5)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['teachers'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Teachers/educators'))
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the mail count was last edited.'));

    return $fields;
  }

}
