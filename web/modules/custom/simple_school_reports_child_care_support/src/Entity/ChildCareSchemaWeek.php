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
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaWeekAccessControlHandler;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaWeekInterface;
use Drupal\simple_school_reports_child_care_support\ChildCareSchemaWeekListBuilder;
use Drupal\simple_school_reports_child_care_support\Form\ChildCareSchemaWeekForm;
use Drupal\simple_school_reports_child_care_support\Routing\ChildCareSchemaWeekHtmlRouteProvider;
use Drupal\simple_school_reports_core\Utilities\TimeToStringUtils;
use Drupal\time_field\Time;
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
    'access' => ChildCareSchemaWeekAccessControlHandler::class,
    'form' => [
      'add' => ChildCareSchemaWeekForm::class,
      'edit' => ChildCareSchemaWeekForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/ssr-child-care-schema-week',
    'add-form' => '/ssr-child-care-schema-week/add',
    'canonical' => '/ssr-child-care-schema-week/{ssr_child_care_schema_week}',
    'edit-form' => '/ssr-child-care-schema-week/{ssr_child_care_schema_week}/edit',
    'delete-form' => '/ssr-child-care-schema-week/{ssr_child_care_schema_week}/delete',
    'delete-multiple-form' => '/admin/content/ssr-child-care-schema-week/delete-multiple',
  ],
  admin_permission: 'administer ssr_child_care_schema_week',
  base_table: 'ssr_child_care_schema_week',
  label_count: [
    'singular' => '@count child care schema weeks',
    'plural' => '@count child care schema weeks',
  ],
  field_ui_base_route: 'entity.ssr_child_care_schema_week.settings',
)]
class ChildCareSchemaWeek extends ContentEntityBase implements ChildCareSchemaWeekInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;


  protected ChildCareSchemaInterface|null|false $parentSchema = FALSE;

  protected function hasDay(int $day): bool {
    return !$this->get('from_' . $day)->isEmpty() && !$this->get('to_' . $day)->isEmpty();
  }

  protected function getDayLength(int $day): int {
    $length = 0;
    if ($this->hasDay($day)) {
      $from = $this->get('from_' . $day)->value ?? 0;
      $to = $this->get('to_' . $day)->value ?? 0;
      $length = abs($to - $from);
    }
    return $length;
  }

  protected function calculateLabel(): string {
    $day_map = [
      1 => t('Mon'),
      2 => t('Tue'),
      3 => t('Wed'),
      4 => t('Thu'),
      5 => t('Fri'),
      6 => t('Sat'),
      7 => t('Sun'),
    ];

    $total_length = 0;
    $label_parts = [];
    for ($day_index = 1; $day_index <= 7; $day_index++) {
      $day_label = $day_map[$day_index];
      $length = $this->getDayLength($day_index);
      $total_length += $length;
      if ($length > 0) {

        $from = $this->get('from_' . $day_index)->value ?? 0;
        $time = Time::createFromTimestamp($from);
        $from = $time->format('H:i');

        $to = $this->get('to_' . $day_index)->value ?? 0;
        $time = Time::createFromTimestamp($to);
        $to = $time->format('H:i');

        $label_parts[] = "$day_label $from-$to";
      }
    }
    $label = implode(', ', $label_parts);
    $label .= !empty($label) ? ' - ' : '';

    $label .= TimeToStringUtils::formatTimeLength($total_length);

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->calculateLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getParentSchema(): ?ChildCareSchemaInterface {
    if ($this->isNew()) {
      return NULL;
    }

    if ($this->parentSchema === FALSE) {
      $this->parentSchema = NULL;
      $schemas = $this->entityTypeManager()->getStorage('ssr_child_care_schema')->loadByProperties(['weeks' => $this->id()]);
      if (count($schemas) > 0) {
        $this->parentSchema = reset($schemas);
      }
    }
    return $this->parentSchema;

  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $day_map = [
      1 => t('Monday'),
      2 => t('Tuesday'),
      3 => t('Wednesday'),
      4 => t('Thursday'),
      5 => t('Friday'),
      6 => t('Saturday'),
      7 => t('Sunday'),
    ];
    for ($day_index = 1; $day_index <= 7; $day_index++) {

      $day_label = $day_map[$day_index];

      $fields['from_' . $day_index] = BaseFieldDefinition::create('time')
        ->setLabel(t('From'))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['to_' . $day_index] = BaseFieldDefinition::create('time')
        ->setLabel(t('To'))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);
    }

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
