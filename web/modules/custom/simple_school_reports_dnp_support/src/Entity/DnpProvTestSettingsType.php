<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Dnp Provisioning Test Settings type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "dnp_prov_test_settings_type",
 *   label = @Translation("Dnp Provisioning Test Settings type"),
 *   label_collection = @Translation("Dnp Provisioning Test Settings types"),
 *   label_singular = @Translation("dnp provisioning test settings type"),
 *   label_plural = @Translation("dnp provisioning test settingss types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dnp provisioning test settingss type",
 *     plural = "@count dnp provisioning test settingss types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_dnp_support\Form\DnpProvTestSettingsTypeForm",
 *       "edit" = "Drupal\simple_school_reports_dnp_support\Form\DnpProvTestSettingsTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\simple_school_reports_dnp_support\DnpProvTestSettingsTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer dnp_prov_test_settings types",
 *   bundle_of = "dnp_prov_test_settings",
 *   config_prefix = "dnp_prov_test_settings_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/dnp_prov_test_settings_types/add",
 *     "edit-form" = "/admin/structure/dnp_prov_test_settings_types/manage/{dnp_prov_test_settings_type}",
 *     "delete-form" = "/admin/structure/dnp_prov_test_settings_types/manage/{dnp_prov_test_settings_type}/delete",
 *     "collection" = "/admin/structure/dnp_prov_test_settings_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class DnpProvTestSettingsType extends ConfigEntityBundleBase {

  /**
   * The machine name of this dnp provisioning test settings type.
   */
  protected string $id;

  /**
   * The human-readable name of the dnp provisioning test settings type.
   */
  protected string $label;

}
