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
use Drupal\simple_school_reports_child_care_support\ChildCareCheckInAccessControlHandler;
use Drupal\simple_school_reports_child_care_support\ChildCareCheckInInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareCheckInListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareCheckInForm;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the child care check in entity class.
 */
#[ContentEntityType(
  id: 'ssr_child_care_check_in',
  label: new TranslatableMarkup('Child care check in'),
  label_collection: new TranslatableMarkup('Child care check ins'),
  label_singular: new TranslatableMarkup('child care check in'),
  label_plural: new TranslatableMarkup('child care check ins'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCareCheckInListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ChildCareCheckInAccessControlHandler::class,
    'form' => [
      'add' => ChildCareCheckInForm::class,
      'edit' => ChildCareCheckInForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-child-care-check-in',
    'add-form' => '/ssr-child-care-check-in/add',
    'canonical' => '/ssr-child-care-check-in/{ssr_child_care_check_in}',
    'edit-form' => '/ssr-child-care-check-in/{ssr_child_care_check_in}/edit',
    'delete-form' => '/ssr-child-care-check-in/{ssr_child_care_check_in}/delete',
    'delete-multiple-form' => '/admin/content/ssr-child-care-check-in/delete-multiple',
  ],
  admin_permission: 'administer ssr_child_care_check_in',
  base_table: 'ssr_child_care_check_in',
  label_count: [
    'singular' => '@count child care check ins',
    'plural' => '@count child care check ins',
  ],
  field_ui_base_route: 'entity.ssr_child_care_check_in.settings',
)]
class ChildCareCheckIn extends ContentEntityBase implements ChildCareCheckInInterface {

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

    $label = 'Incheckning';
    /** @var int|null $from */
    $from = $this->get('from')->value ? (int) $this->get('from')->value : NULL;
    /** @var \Drupal\user\UserInterface|null $student */
    $student = $this->get('student')->entity;
    if ($from && $student) {
      $from_date = new \DateTime();
      $from_date->setTimestamp($from);
      $label .= ' ' . $student->getDisplayName() . ' ' . $from_date->format('Y-m-d');
    }
    $this->set('label', $label);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('From'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('To'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['student'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Student'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['child_care'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Child care group'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'ssr_child_care')
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
