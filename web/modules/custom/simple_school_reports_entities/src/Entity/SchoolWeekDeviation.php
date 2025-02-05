<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_entities\SchoolWeekDeviationInterface;
use Drupal\time_field\Time;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the school week deviation entity class.
 *
 * @ContentEntityType(
 *   id = "school_week_deviation",
 *   label = @Translation("School week deviation"),
 *   label_collection = @Translation("School week deviations"),
 *   label_singular = @Translation("school week deviation"),
 *   label_plural = @Translation("school week deviations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count school week deviations",
 *     plural = "@count school week deviations",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_entities\SchoolWeekDeviationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_entities\SchoolWeekDeviationAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\SchoolWeekDeviationForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\SchoolWeekDeviationForm",
 *       "delete" = "Drupal\simple_school_reports_entities\Form\SchoolWeekDeviationDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "school_week_deviation",
 *   admin_permission = "administer school_week_deviation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/school-week-deviation",
 *     "add-form" = "/school-week-deviation/add",
 *     "canonical" = "/school-week-deviation/{school_week_deviation}",
 *     "edit-form" = "/school-week-deviation/{school_week_deviation}/edit",
 *     "delete-form" = "/school-week-deviation/{school_week_deviation}/delete",
 *     "delete-multiple-form" = "/admin/content/school-week-deviation/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.school_week_deviation.settings",
 *  constraints = {
 *    "SsrSchoolWeekDeviationConstraint" = {}
 *  }
 * )
 */
final class SchoolWeekDeviation extends ContentEntityBase implements SchoolWeekDeviationInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use StringTranslationTrait;

  protected function getLabelBase(): string {
    $label = '';
    $from = $this->get('from_date')->value;
    $to = $this->get('to_date')->value;
    if ($from && $to && is_numeric($from) && is_numeric($to)) {

      if ($from > $to) {
        $tmp = $from;
        $from = $to;
        $to = $tmp;
        $this->set('from_date', $from);
        $this->set('to_date', $to);
      }

      $from = (new \DateTime())->setTimestamp((int) $from)->format('Y-m-d');
      $to = (new \DateTime())->setTimestamp((int) $to)->format('Y-m-d');

      if ($from === $to) {
        $label = $from;
      }
      else {
        $label = $from . ' - ' . $to;
      }
    }

    $no_teaching = !!($this->get('no_teaching')->value ?? FALSE);
    if ($no_teaching) {
      $label .= ', ' . 'ledig';
    }
    else {
      $from_time = $this->get('from')->value ?? NULL;
      if ($from_time && is_numeric($from_time)) {
        $time = Time::createFromTimestamp((int) $from_time);
        $from_time = $time->format('H:i');
      }

      $to_time = $this->get('to')->value ?? NULL;
      if ($to_time && is_numeric($to_time)) {
        $time = Time::createFromTimestamp($to_time);
        $to_time = $time->format('H:i');
      }

      if ($from_time && $to_time) {
        $label .= ', skoldag: ' . $from_time . ' - ' . $to_time;
      }
    }

    $deviation_type = $this->get('deviation_type')->entity;
    if ($deviation_type) {
      $label .= ' (' . $deviation_type->label() . ')';
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    $from = $this->get('from_date')->value;
    $to = $this->get('to_date')->value;

    if (!$from || !$to) {
      throw new \RuntimeException('Both from and to dates must be set.');
    }

    if ($from > $to) {
      $this->set('from_date', $to);
      $this->set('to_date', $from);
    }
    $this->set('label', $this->getLabelBase());

    $no_teaching = !!($this->get('no_teaching')->value ?? FALSE);
    if ($no_teaching) {
      $this->set('from', NULL);
      $this->set('to', NULL);
    }

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
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the school week deviation was last edited.'));

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

    $fields['length'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('School day length'))
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 1200)
      ->setDescription(t('The length of the school day for @day_label in minutes.', ['@day_label' => t('this deviation')]))
      ->setSetting('unsigned', TRUE)
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

    $fields['no_teaching'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('No teaching hours (free)'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Free day'))
      ->setSetting('off_label', t('School day'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['deviation_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Deviation type'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['school_week_deviation_type' => 'school_week_deviation_type'],
        'auto_create' => TRUE,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['grade'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('School grade'))
      ->setDescription(t('The school grades that this school week deviation applies to.'))
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_school_week_deviation_grades')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
