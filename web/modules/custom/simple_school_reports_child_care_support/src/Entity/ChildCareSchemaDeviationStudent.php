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
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaDeviationStudentAccessControlHandler;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaDeviationStudentInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaDeviationStudentListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareSchemaDeviationStudentForm;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the child care schema deviation student entity class.
 */
#[ContentEntityType(
  id: 'ssr_cc_deviation_student',
  label: new TranslatableMarkup('Child care schema deviation student'),
  label_collection: new TranslatableMarkup('Child care schema deviation students'),
  label_singular: new TranslatableMarkup('child care schema deviation student'),
  label_plural: new TranslatableMarkup('child care schema deviation students'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCareSchemaDeviationStudentListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ChildCareSchemaDeviationStudentAccessControlHandler::class,
    'form' => [
      'add' => ChildCareSchemaDeviationStudentForm::class,
      'edit' => ChildCareSchemaDeviationStudentForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-cc-deviation-student',
    'add-form' => '/ssr-child-care-schema-deviation-student/add',
    'canonical' => '/ssr-child-care-schema-deviation-student/{ssr_cc_deviation_student}',
    'edit-form' => '/ssr-child-care-schema-deviation-student/{ssr_cc_deviation_student}/edit',
    'delete-form' => '/ssr-child-care-schema-deviation-student/{ssr_cc_deviation_student}/delete',
    'delete-multiple-form' => '/admin/content/ssr-cc-deviation-student/delete-multiple',
  ],
  admin_permission: 'administer ssr_cc_deviation_student',
  base_table: 'ssr_cc_deviation_student',
  label_count: [
    'singular' => '@count child care schema deviation students',
    'plural' => '@count child care schema deviation students',
  ],
  field_ui_base_route: 'entity.ssr_cc_deviation_student.settings',
)]
class ChildCareSchemaDeviationStudent extends ContentEntityBase implements ChildCareSchemaDeviationStudentInterface {

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

    $fields['student'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Student'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Deviation from'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Deviation to'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from'] = BaseFieldDefinition::create('time')
      ->setLabel(t('School day start'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to'] = BaseFieldDefinition::create('time')
      ->setLabel(t('School day end'))
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
