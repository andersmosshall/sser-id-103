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

    if (!$this->get('child_care')->isEmpty()) {
      $this->set('student', NULL);
      $this->set('type', self::SCHEMA_TYPE_CHILD_CARE);
      $this->set('label', 'Grundschema - fritidshem');
    }
    if (!$this->get('student')->isEmpty()) {
      $this->set('child_care', NULL);
      $this->set('type', self::SCHEMA_TYPE_STUDENT);
      $this->set('label', 'Grundschema - omsorgsbehov');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    if (!$this->get('child_care')->isEmpty()) {
      $tags[] = 'child_care_schema_list:child_care:' . $this->get('child_care')->target_id;
    }
    if (!$this->get('student')->isEmpty()) {
      $tags[] = 'child_care_schema_list:student:' . $this->get('student')->target_id;
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function isFuture(): bool {
    $from = $this->get('from')->value;
    if (!$from) {
      return FALSE;
    }

    /** @var \Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_child_care_support.child_care');

    $threshold = new \DateTime();
    $threshold->setTime(0, 0, 0);

    $future_min_limit = $service->getSettings()['future_min_limit'];
    $threshold->add(new \DateInterval('P' . $future_min_limit . 'D'));

    return $from > $threshold->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    if ($this->isFuture()) {
      return FALSE;
    }
    if ($this->isNew()) {
      return FALSE;
    }

    $child_care_id = $this->get('child_care')->target_id;
    $student_id = $this->get('student')->target_id;
    if (!$child_care_id && !$student_id) {
      return FALSE;
    }

    /** @var \Drupal\simple_school_reports_child_care_support\Service\ChildCareSchemaServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_child_care_support.child_care_schema');

    if ($child_care_id) {
      $active_schema_id = $service->getActiveChildCareSchema($child_care_id, new \DateTime());
    } else {
      $active_schema_id = NULL;
    }
    return $active_schema_id == $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isEditable(): bool {
    if ($this->isNew()) {
      return TRUE;
    }

    if ($this->isFuture()) {
      return TRUE;
    }

    return $this->isActive();
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

    // Only one of child_care or student can be set.
    $fields['child_care'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Child care group'))
      ->setSetting('target_type', 'ssr_child_care')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['student'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Student'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Schema type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_child_care_support_schema_types')
      ->setDefaultValue('default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('From'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('To'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weeks'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reoccurring weeks'))
      ->setSetting('target_type', 'ssr_child_care_schema_week')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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
