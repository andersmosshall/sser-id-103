<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simple_school_reports_entities\CalendarEventInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the calendar event entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_calendar_event",
 *   label = @Translation("Calendar Event"),
 *   label_collection = @Translation("Calendar Events"),
 *   label_singular = @Translation("calendar event"),
 *   label_plural = @Translation("calendar events"),
 *   label_count = @PluralTranslation(
 *     singular = "@count calendar events",
 *     plural = "@count calendar events",
 *   ),
 *   bundle_label = @Translation("Calendar Event type"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_entities\CalendarEventListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_entities\CalendarEventAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\CalendarEventForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\CalendarEventForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_calendar_event",
 *   admin_permission = "administer ssr_calendar_event types",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-calendar-event",
 *     "add-form" = "/ssr-calendar-event/add/{ssr_calendar_event_type}",
 *     "add-page" = "/ssr-calendar-event/add",
 *     "canonical" = "/ssr-calendar-event/{ssr_calendar_event}",
 *     "edit-form" = "/ssr-calendar-event/{ssr_calendar_event}/edit",
 *     "delete-form" = "/ssr-calendar-event/{ssr_calendar_event}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-calendar-event/delete-multiple",
 *   },
 *   bundle_entity_type = "ssr_calendar_event_type",
 *   field_ui_base_route = "entity.ssr_calendar_event_type.edit_form",
 * )
 */
final class CalendarEvent extends ContentEntityBase implements CalendarEventInterface {

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

    if ($this->isNew() || empty($this->label())) {
      $label = '-';
      $from = $this->get('from')->value;
      $to = $this->get('to')->value;
      if ($from && $to) {
        $label = date('Y-m-d H:i', (int) $from) . ' - ' . date('Y-m-d H:i', (int) $to);
      }
      $this->set('label', $label);
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

    $fields['identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Identifier'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('From'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('To'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['meta'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Meta data in JSON format'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Enabled'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cancelled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cancelled'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Completed'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
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
      ->setDescription(t('The time that the calendar event was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the calendar event was last edited.'));

    return $fields;
  }

}
