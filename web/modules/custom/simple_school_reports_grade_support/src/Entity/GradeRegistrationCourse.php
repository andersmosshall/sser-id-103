<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_grade_support\GradeRegistrationCourseInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the course to grade entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_grade_reg_course",
 *   label = @Translation("Course to grade"),
 *   label_collection = @Translation("Course to grades"),
 *   label_singular = @Translation("course to grade"),
 *   label_plural = @Translation("course to grades"),
 *   label_count = @PluralTranslation(
 *     singular = "@count course to grades",
 *     plural = "@count course to grades",
 *   ),
 *   bundle_label = @Translation("Course to grade type"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_grade_support\GradeRegistrationCourseListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_grade_support\GradeRegistrationCourseAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_grade_support\Form\GradeRegistrationCourseForm",
 *       "edit" = "Drupal\simple_school_reports_grade_support\Form\GradeRegistrationCourseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_grade_reg_course",
 *   admin_permission = "administer ssr_grade_reg_course types",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-grade-registration-course",
 *     "add-form" = "/ssr-grade-registration-course/add/{ssr_grade_reg_course_type}",
 *     "add-page" = "/ssr-grade-registration-course/add",
 *     "canonical" = "/ssr-grade-registration-course/{ssr_grade_reg_course}",
 *     "edit-form" = "/ssr-grade-registration-course/{ssr_grade_reg_course}/edit",
 *     "delete-form" = "/ssr-grade-registration-course/{ssr_grade_reg_course}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-grade-registration-course/delete-multiple",
 *   },
 *   bundle_entity_type = "ssr_grade_reg_course_type",
 *   field_ui_base_route = "entity.ssr_grade_reg_course_type.edit_form",
 * )
 */
final class GradeRegistrationCourse extends ContentEntityBase implements GradeRegistrationCourseInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  public static function makeLabelFromCourse(?NodeInterface $course = NULL): TranslatableMarkup | string {
    if (!$course || $course->bundle() !== 'course') {
      return t('Course not set');
    }
    $label = $course->label();

    $grading_teachers = $course->get('field_grading_teacher')->referencedEntities();

    if (count($grading_teachers) > 0) {
      $label .= ' (' . implode(', ', array_map(function ($teacher) {
        return $teacher->label();
      }, $grading_teachers)) . ')';
    }
    else {
      $label .= ' (' . t('NO GRADING TEACHERS!') . ')';
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }

    if (!$this->isNew()) {
      if ($this->original->get('course')->target_id !== $this->get('course')->target_id) {
        // Course changed, reset registration status.
        $this->set('registration_status', self::REGISTRATION_STATUS_NOT_STARTED);
        $this->set('postpone', NULL);
      }
    }

    if ($this->isSyncing()) {
      return;
    }

    if ($this->get('registration_status')->value === self::REGISTRATION_STATUS_DONE) {
      $this->set('postpone', NULL);
    }

    $this->set('label', self::makeLabelFromCourse($this->get('course')->entity));
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return self::makeLabelFromCourse($this->get('course')->entity);
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
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE)
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the grade was last edited.'));

    $fields['course'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Course'))
      ->setDescription(t('Search for course by name or course code.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'views')
      ->setSetting('handler_settings', [
        'match_limit' => 10,
        'match_operator' => 'CONTAINS',
        'view' => [
          'display_name' => 'gradable_all',
          'view_name' => 'course_reference',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['registration_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Registration status'))
      ->setSetting('allowed_values_function', 'simple_school_reports_grade_support_registration_status_options')
      ->setRequired(TRUE)
      ->setDefaultValue(GradeRegistrationCourseInterface::REGISTRATION_STATUS_NOT_STARTED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['form_data_stash'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Form data stash'));

    return $fields;
  }

}
