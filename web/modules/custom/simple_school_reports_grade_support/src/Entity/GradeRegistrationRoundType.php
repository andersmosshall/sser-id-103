<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Grade registration round type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "ssr_grade_reg_round_type",
 *   label = @Translation("Grade registration round type"),
 *   label_collection = @Translation("Grade registration round types"),
 *   label_singular = @Translation("grade registration round type"),
 *   label_plural = @Translation("grade registration rounds types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count grade registration rounds type",
 *     plural = "@count grade registration rounds types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_grade_support\Form\GradeRegistrationRoundTypeForm",
 *       "edit" = "Drupal\simple_school_reports_grade_support\Form\GradeRegistrationRoundTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\simple_school_reports_grade_support\GradeRegistrationRoundTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer ssr_grade_reg_round types",
 *   bundle_of = "ssr_grade_reg_round",
 *   config_prefix = "ssr_grade_reg_round_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/ssr_grade_reg_round_types/add",
 *     "edit-form" = "/admin/structure/ssr_grade_reg_round_types/manage/{ssr_grade_reg_round_type}",
 *     "delete-form" = "/admin/structure/ssr_grade_reg_round_types/manage/{ssr_grade_reg_round_type}/delete",
 *     "collection" = "/admin/structure/ssr_grade_reg_round_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class GradeRegistrationRoundType extends ConfigEntityBundleBase {

  /**
   * The machine name of this grade registration round type.
   */
  protected string $id;

  /**
   * The human-readable name of the grade registration round type.
   */
  protected string $label;

}
