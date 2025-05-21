<?php

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Meeting type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "ssr_meeting_type",
 *   label = @Translation("Meeting type"),
 *   label_collection = @Translation("Meeting types"),
 *   label_singular = @Translation("meeting type"),
 *   label_plural = @Translation("meetings types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count meetings type",
 *     plural = "@count meetings types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\SsrMeetingTypeForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\SsrMeetingTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\simple_school_reports_entities\SsrMeetingTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer meeting types",
 *   bundle_of = "ssr_meeting",
 *   config_prefix = "ssr_meeting_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/ssr_meeting_types/add",
 *     "edit-form" = "/admin/structure/ssr_meeting_types/manage/{ssr_meeting_type}",
 *     "delete-form" = "/admin/structure/ssr_meeting_types/manage/{ssr_meeting_type}/delete",
 *     "collection" = "/admin/structure/ssr_meeting_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class SsrMeetingType extends ConfigEntityBundleBase {

  /**
   * The machine name of this meeting type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the meeting type.
   *
   * @var string
   */
  protected $label;

}
