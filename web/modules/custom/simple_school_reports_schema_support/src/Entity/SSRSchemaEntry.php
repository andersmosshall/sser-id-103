<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_schema_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\simple_school_reports_schema_support\SSRSchemaEntryInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the ssr schema entry entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_schema_entry",
 *   label = @Translation("SSR schema entry"),
 *   label_collection = @Translation("SSR schema entries"),
 *   label_singular = @Translation("ssr schema entry"),
 *   label_plural = @Translation("ssr schema entries"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ssr schema entries",
 *     plural = "@count ssr schema entries",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_schema_support\SSRSchemaEntryListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_schema_support\SSRSchemaEntryAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_schema_support\Form\SSRSchemaEntryForm",
 *       "edit" = "Drupal\simple_school_reports_schema_support\Form\SSRSchemaEntryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_schema_entry",
 *   admin_permission = "administer ssr_schema_entry",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-schema-entry",
 *     "add-form" = "/ssr-schema-entry/add",
 *     "canonical" = "/ssr-schema-entry/{ssr_schema_entry}",
 *     "edit-form" = "/ssr-schema-entry/{ssr_schema_entry}/edit",
 *     "delete-form" = "/ssr-schema-entry/{ssr_schema_entry}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-schema-entry/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_schema_entry.settings",
 * )
 */
final class SSRSchemaEntry extends ContentEntityBase implements SSRSchemaEntryInterface {

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

    $this->set('label', $this->calculateLabel());
  }

  protected function calculateLabel(): string {
    $label = '';

    if  ($this->get('week_day')->value) {
      $day = ssr_day_map()[$this->get('week_day')->value] ?? NULL;
      if ($day) {
        $label = mb_ucfirst((string) $day);
      }
    }


    $base_date = new \DateTime();
    $base_date->setTime(0, 0, 0);

    if ($this->get('from')->value) {
      $from = new \DateTime();
      $from->setTimestamp((int) $this->get('from')->value + $base_date->getTimestamp());
      $label .= ' ' . $from->format('H:i');

      if ($this->get('length')->value) {
        $length = $this->get('length')->value;
        $to = new \DateTime();
        $to->setTimestamp((int) $this->get('from')->value + $length * 60 + $base_date->getTimestamp());
        $label .= ' - ' . $to->format('H:i');
      }
    }

    if (empty($label)) {
      $label = '-';
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->calculateLabel();
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
      ->setLabel(t('Status'))
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
      ->setDescription(t('The time that the ssr schema entry was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the ssr schema entry was last edited.'));

    $fields['source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Source'))
      ->setSetting('allowed_values_function', 'simple_school_reports_schema_support_schema_source_options')
      ->setRequired(TRUE)
      ->setDefaultValue('ssr')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['lesson_id'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setReadOnly(TRUE);

    $fields['week_day'] =  BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Day'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'ssr_day_map')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from'] = BaseFieldDefinition::create('time')
      ->setLabel(t('Lesson start'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['length'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Lesson duration'))
      ->setDescription(t('Set lesson length in minutes'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 1200)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['deviated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Deviated group or periodicity'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $number_of_groups = 5;

    $fields['relevant_groups'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Number of groups'))
      ->setRequired(TRUE)
      ->setDefaultValue(2)
      ->setSetting('allowed_values_function', 'simple_school_reports_schema_support_schema_subgroups')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    for ($i = 1; $i <= $number_of_groups; $i++) {
      $fields['display_name_' . $i] = BaseFieldDefinition::create('string')
        ->setLabel(t('Group name @number', ['@number' => $i]))
        ->setSetting('max_length', 255)
        ->setRequired(TRUE)
        ->setDefaultValue('Grupp ' . $i)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['periodicity_' . $i] = BaseFieldDefinition::create('list_string')
        ->setLabel(t('Periodicity for group @number', ['@number' => $i]))
        ->setSetting('allowed_values_function', 'simple_school_reports_schema_support_schema_periodicity_options')
        ->setRequired(TRUE)
        ->setDefaultValue('weekly')
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['custom_periodicity_' . $i] = BaseFieldDefinition::create('list_integer')
        ->setLabel(t('Specific periodicity for group @number', ['@number' => $i]))
        ->setRequired(TRUE)
        ->setDefaultValue(2)
        ->setSetting('allowed_values_function', 'simple_school_reports_schema_support_schema_custom_periodicity_options')
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['custom_periodicity_start_' . $i] = BaseFieldDefinition::create('timestamp')
        ->setLabel(t('With start at, for group @number', ['@number' => $i]))
        ->setRequired(TRUE)
        ->setDefaultValue((new \DateTime())->setTime(12, 0, 0)->getTimestamp())
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

      $fields['students_' . $i] = BaseFieldDefinition::create('entity_reference')
        ->setTranslatable(TRUE)
        ->setLabel(t('Students, group @number', ['@number' => $i]))
        ->setDescription(t('Students not relevant for the student list for the corresponding course will be ignored.'))
        ->setSetting('target_type', 'user')
        ->setSetting('handler', 'views')
        ->setSetting('handler_settings', [
          'view' => [
            'arguments' => [],
            'display_name' => 'active_students',
            'view_name' => 'student_reference',
          ],
        ])
        ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);
    }

    return $fields;
  }

}
