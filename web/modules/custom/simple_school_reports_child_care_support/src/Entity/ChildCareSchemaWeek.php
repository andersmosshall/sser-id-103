<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaWeekInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaWeekListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareSchemaWeekForm;
use Drupal\simple_school_reports_child_care_support\Routing\ChildCareSchemaWeekHtmlRouteProvider;
use Drupal\views\EntityViewsData;

/**
 * Defines the child care schema week entity class.
 */
#[ContentEntityType(
  id: 'ssr_child_care_schema_week',
  label: new TranslatableMarkup('Child care schema week'),
  label_collection: new TranslatableMarkup('Child care schema weeks'),
  label_singular: new TranslatableMarkup('child care schema week'),
  label_plural: new TranslatableMarkup('child care schema weeks'),
  entity_keys: [
    'id' => 'id',
    'label' => 'id',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCareSchemaWeekListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => ChildCareSchemaWeekForm::class,
      'edit' => ChildCareSchemaWeekForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => ChildCareSchemaWeekHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-child-care-schema-week',
    'add-form' => '/ssr-child-care-schema-week/add',
    'canonical' => '/ssr-child-care-schema-week/{ssr_child_care_schema_week}',
    'edit-form' => '/ssr-child-care-schema-week/{ssr_child_care_schema_week}',
    'delete-form' => '/ssr-child-care-schema-week/{ssr_child_care_schema_week}/delete',
    'delete-multiple-form' => '/admin/content/ssr-child-care-schema-week/delete-multiple',
  ],
  admin_permission: 'administer ssr_child_care_schema_week',
  base_table: 'ssr_child_care_schema_week',
  label_count: [
    'singular' => '@count child care schema weeks',
    'plural' => '@count child care schema weeks',
  ],
)]
class ChildCareSchemaWeek extends ContentEntityBase implements ChildCareSchemaWeekInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the child care schema week was created.'))
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
      ->setDescription(t('The time that the child care schema week was last edited.'));

    return $fields;
  }

}
