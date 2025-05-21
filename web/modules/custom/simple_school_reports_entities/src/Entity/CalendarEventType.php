<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Calendar Event type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "ssr_calendar_event_type",
 *   label = @Translation("Calendar Event type"),
 *   label_collection = @Translation("Calendar Event types"),
 *   label_singular = @Translation("calendar event type"),
 *   label_plural = @Translation("calendar events types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count calendar events type",
 *     plural = "@count calendar events types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\CalendarEventTypeForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\CalendarEventTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\simple_school_reports_entities\CalendarEventTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer ssr_calendar_event types",
 *   bundle_of = "ssr_calendar_event",
 *   config_prefix = "ssr_calendar_event_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/ssr_calendar_event_types/add",
 *     "edit-form" = "/admin/structure/ssr_calendar_event_types/manage/{ssr_calendar_event_type}",
 *     "delete-form" = "/admin/structure/ssr_calendar_event_types/manage/{ssr_calendar_event_type}/delete",
 *     "collection" = "/admin/structure/ssr_calendar_event_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class CalendarEventType extends ConfigEntityBundleBase {

  /**
   * The machine name of this calendar event type.
   */
  protected string $id;

  /**
   * The human-readable name of the calendar event type.
   */
  protected string $label;

}
