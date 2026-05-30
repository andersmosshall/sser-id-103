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
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaDeviationAccessControlHandler;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaDeviationInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaDeviationListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareSchemaDeviationForm;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the child care schema deviation entity class.
 */
#[ContentEntityType(
  id: 'ssr_cc_deviation',
  label: new TranslatableMarkup('Child care schema deviation'),
  label_collection: new TranslatableMarkup('Child care schema deviations'),
  label_singular: new TranslatableMarkup('child care schema deviation'),
  label_plural: new TranslatableMarkup('child care schema deviations'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ChildCareSchemaDeviationListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ChildCareSchemaDeviationAccessControlHandler::class,
    'form' => [
      'add' => ChildCareSchemaDeviationForm::class,
      'edit' => ChildCareSchemaDeviationForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-cc-deviation',
    'add-form' => '/ssr-child-care-schema-deviation/add',
    'canonical' => '/ssr-child-care-schema-deviation/{ssr_cc_deviation}',
    'edit-form' => '/ssr-child-care-schema-deviation/{ssr_cc_deviation}/edit',
    'delete-form' => '/ssr-child-care-schema-deviation/{ssr_cc_deviation}/delete',
    'delete-multiple-form' => '/admin/content/ssr-cc-deviation/delete-multiple',
  ],
  admin_permission: 'administer ssr_cc_deviation',
  base_table: 'ssr_cc_deviation',
  label_count: [
    'singular' => '@count child care schema deviations',
    'plural' => '@count child care schema deviations',
  ],
  field_ui_base_route: 'entity.ssr_cc_deviation.settings',
)]
class ChildCareSchemaDeviation extends ContentEntityBase implements ChildCareSchemaDeviationInterface {

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

    $from = $this->get('from_date')->value;
    $to = $this->get('to_date')->value;

    if (!$from || !$to) {
      throw new \InvalidArgumentException('Both from and to dates must be set.');
    }

    if ($from > $to) {
      throw new \InvalidArgumentException('From date must be before to date.');
    }

    $time_from = $this->get('from')->value;
    $time_to = $this->get('to')->value;

    if ($time_from === NULL && $time_to === NULL) {
      return;
    }

    if ($time_from === NULL || $time_to === NULL) {
      throw new \InvalidArgumentException('Both from and to times must be set.');
    }

    if ($time_from > $time_to) {
      throw new \InvalidArgumentException('From time must be before to time.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isFuture(): bool {
    $from = $this->get('from_date')->value;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['child_care'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Child care group'))
      ->setSetting('target_type', 'ssr_child_care')
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

    $fields['deviation_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Deviation type'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['child_care_deviation_type' => 'child_care_deviation_type'],
        'auto_create' => TRUE,
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
