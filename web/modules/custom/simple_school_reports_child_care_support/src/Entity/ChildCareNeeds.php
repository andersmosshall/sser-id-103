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
use Drupal\simple_school_reports_child_care_support\ChildCareNeedsAccessControlHandler;
use Drupal\simple_school_reports_child_care_support\ChildCareNeedsInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareNeedsListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareNeedsForm;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the child care needs entity class.
 */
#[ContentEntityType(
  id: 'ssr_child_care_needs',
  label: new TranslatableMarkup('Child care needs'),
  label_collection: new TranslatableMarkup('Child care needss'),
  label_singular: new TranslatableMarkup('child care needs'),
  label_plural: new TranslatableMarkup('child care needss'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCareNeedsListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ChildCareNeedsAccessControlHandler::class,
    'form' => [
      'add' => ChildCareNeedsForm::class,
      'edit' => ChildCareNeedsForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-child-care-needs',
    'add-form' => '/ssr-child-care-needs/add',
    'canonical' => '/ssr-child-care-needs/{ssr_child_care_needs}',
    'edit-form' => '/ssr-child-care-needs/{ssr_child_care_needs}/edit',
    'delete-form' => '/ssr-child-care-needs/{ssr_child_care_needs}/delete',
    'delete-multiple-form' => '/admin/content/ssr-child-care-needs/delete-multiple',
  ],
  admin_permission: 'administer ssr_child_care_needs',
  base_table: 'ssr_child_care_needs',
  label_count: [
    'singular' => '@count child care needss',
    'plural' => '@count child care needss',
  ],
  field_ui_base_route: 'entity.ssr_child_care_needs.settings',
)]
class ChildCareNeeds extends ContentEntityBase implements ChildCareNeedsInterface {

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
