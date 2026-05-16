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
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaAccessControlHandler;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareSchemaForm;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the child care schema entity class.
 */
#[ContentEntityType(
  id: 'ssr_child_care_schema',
  label: new TranslatableMarkup('Child care schema'),
  label_collection: new TranslatableMarkup('Child care schemas'),
  label_singular: new TranslatableMarkup('child care schema'),
  label_plural: new TranslatableMarkup('child care schemas'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCareSchemaListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ChildCareSchemaAccessControlHandler::class,
    'form' => [
      'add' => ChildCareSchemaForm::class,
      'edit' => ChildCareSchemaForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-child-care-schema',
    'add-form' => '/ssr-child-care-schema/add',
    'canonical' => '/ssr-child-care-schema/{ssr_child_care_schema}',
    'edit-form' => '/ssr-child-care-schema/{ssr_child_care_schema}/edit',
    'delete-form' => '/ssr-child-care-schema/{ssr_child_care_schema}/delete',
    'delete-multiple-form' => '/admin/content/ssr-child-care-schema/delete-multiple',
  ],
  admin_permission: 'administer ssr_child_care_schema',
  base_table: 'ssr_child_care_schema',
  label_count: [
    'singular' => '@count child care schemas',
    'plural' => '@count child care schemas',
  ],
  field_ui_base_route: 'entity.ssr_child_care_schema.settings',
)]
class ChildCareSchema extends ContentEntityBase implements ChildCareSchemaInterface {

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
