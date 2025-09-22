<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Course to grade type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "ssr_grade_reg_course_type",
 *   label = @Translation("Course to grade type"),
 *   label_collection = @Translation("Course to grade types"),
 *   label_singular = @Translation("course to grade type"),
 *   label_plural = @Translation("course to grades types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count course to grades type",
 *     plural = "@count course to grades types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_grade_support\Form\GradeRegistrationCourseTypeForm",
 *       "edit" = "Drupal\simple_school_reports_grade_support\Form\GradeRegistrationCourseTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\simple_school_reports_grade_support\GradeRegistrationCourseTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer ssr_grade_reg_course types",
 *   bundle_of = "ssr_grade_reg_course",
 *   config_prefix = "ssr_grade_reg_course_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/ssr_grade_reg_course_types/add",
 *     "edit-form" = "/admin/structure/ssr_grade_reg_course_types/manage/{ssr_grade_reg_course_type}",
 *     "delete-form" = "/admin/structure/ssr_grade_reg_course_types/manage/{ssr_grade_reg_course_type}/delete",
 *     "collection" = "/admin/structure/ssr_grade_reg_course_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class GradeRegistrationCourseType extends ConfigEntityBundleBase {

  /**
   * The machine name of this course to grade type.
   */
  protected string $id;

  /**
   * The human-readable name of the course to grade type.
   */
  protected string $label;

}
