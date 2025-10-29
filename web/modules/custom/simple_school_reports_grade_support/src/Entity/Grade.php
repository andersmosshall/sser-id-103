<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_grade_support\GradeInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the grade entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_grade",
 *   label = @Translation("Grade"),
 *   label_collection = @Translation("Grades"),
 *   label_singular = @Translation("grade"),
 *   label_plural = @Translation("grades"),
 *   label_count = @PluralTranslation(
 *     singular = "@count grades",
 *     plural = "@count grades",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_grade_support\GradeListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_grade_support\GradeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_grade_support\Form\GradeForm",
 *       "edit" = "Drupal\simple_school_reports_grade_support\Form\GradeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "ssr_grade",
 *   revision_table = "ssr_grade_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer ssr_grade",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-grade",
 *     "add-form" = "/ssr-grade/add",
 *     "canonical" = "/ssr-grade/{ssr_grade}",
 *     "edit-form" = "/ssr-grade/{ssr_grade}/edit",
 *     "delete-form" = "/ssr-grade/{ssr_grade}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-grade/delete-multiple",
 *     "revision" = "/ssr-grade/{ssr_grade}/revision/{ssr_grade_revision}/view",
 *     "revision-delete-form" = "/ssr-grade/{ssr_grade}/revision/{ssr_grade_revision}/delete",
 *     "revision-revert-form" = "/ssr-grade/{ssr_grade}/revision/{ssr_grade_revision}/revert",
 *     "version-history" = "/ssr-grade/{ssr_grade}/revisions",
 *   },
 *   field_ui_base_route = "entity.ssr_grade.settings",
 * )
 */
final class Grade extends RevisionableContentEntityBase implements GradeInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  protected function assertNoForbiddenChanges(): bool {
    if ($this->isNew()) {
      return TRUE;
    }

    /** @var \Drupal\simple_school_reports_grade_support\GradeInterface|null $original */
    $original = $this->original ?? null;
    if (!($original instanceof GradeInterface)) {
      throw new \RuntimeException('Original entity is not an instance of GradeInterface. Grade: ' . $this->id());
    }
    if ($this->get('student')->target_id !== $original->get('student')->target_id) {
      throw new \RuntimeException('Forbidden change: student cannot be changed. Grade: ' . $this->id());
    }
    if ($this->get('syllabus')->target_id !== $original->get('syllabus')->target_id) {
      throw new \RuntimeException('Forbidden change: syllabus cannot be changed. Grade: ' . $this->id());
    }
    if ($this->get('identifier')->value !== $original->get('identifier')->value) {
      throw new \RuntimeException('Forbidden change: identifier cannot be changed. Grade: ' . $this->id());
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setIdentifier(): self {
    $identifier = 's.' . $this->get('syllabus')->target_id . '.u.' . $this->get('student')->target_id;
    $this->set('identifier', $identifier);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sanitizeFields(): self {
    if ($this->get('identifier')->isEmpty()) {
      $this->setIdentifier();
    }
    if ($this->get('label')->isEmpty()) {
      $this->set('label', $this->get('identifier')->value);
    }
    if ($this->get('registered')->isEmpty()) {
      $this->set('registered', \Drupal::time()->getRequestTime());
    }

    if (!$this->get('exclude_reason')->isEmpty()) {
      $fields_to_clear = [
        'main_grader',
        'joint_grading_by',
        'grade',
        'trial',
      ];
      foreach ($fields_to_clear as $field_to_clear) {
        $this->set($field_to_clear, NULL);
      }
    }

    if ($this->get('term_index')->isEmpty()) {
      /** @var \Drupal\simple_school_reports_core\Service\TermServiceInterface $term_service */
      $term_service = \Drupal::service('simple_school_reports_core.term_service');
      $this->set('term_index', $term_service->getDefaultTermIndex());
    }

    // Remove user from joint_grading_by if it is the same as main_grader.
    $joint_grading_by_uids = array_column($this->get('joint_grading_by')->getValue() ?? [], 'target_id');
    $main_grader_uid = $this->get('main_grader')->target_id;
    if (in_array($main_grader_uid, $joint_grading_by_uids)) {
      $new_joint_grading_by = array_filter($joint_grading_by_uids, function ($uid) use ($main_grader_uid) {
        return $uid != $main_grader_uid;
      });
      $this->set('joint_grading_by', $new_joint_grading_by);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanges(?GradeInterface $original = NULL): bool {
    if ($this->isNew()) {
      return TRUE;
    }

    if (!$original) {
      $original = $this->original ?? null;
    }

    if (!($original instanceof GradeInterface)) {
      throw new \RuntimeException('Original entity is not an instance of GradeInterface. Grade: ' . $this->id());
    }

    $target_id_changes = [
      'grade',
      'main_grader',
      'course',
    ];

    foreach ($target_id_changes as $target_id_change) {
      $original_value = $original->get($target_id_change)->target_id;
      $new_value = $this->get($target_id_change)->target_id;
      if ($original_value !== $new_value) {
        return TRUE;
      }
    }

    $target_id_changes_multiple = [
      'joint_grading_by',
    ];
    foreach ($target_id_changes_multiple as $target_id_change) {
      $original_value = array_column($original->get($target_id_change)->getValue(), 'target_id');
      $new_value = array_column($this->get($target_id_change)->getValue(), 'target_id');
      $original_value = array_unique($original_value);
      sort($original_value);
      $new_value = array_unique($new_value);
      sort($new_value);

      if ($original_value != $new_value) {
        return TRUE;
      }
    }

    $value_changes = [
      'diploma_project_description',
      'diploma_project_label',
      'correction_type',
      'remark',
      'trial',
      'exclude_reason',
      'term_index',
    ];
    foreach ($value_changes as $value_change) {
      $original_value = $original->get($value_change)->value;
      $new_value = $this->get($value_change)->value;
      if ($original_value !== $new_value) {
        return TRUE;
      }
    }

    return FALSE;
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
    $this->sanitizeFields();

    if ($this->isSyncing()) {
      return;
    }


    $this->assertNoForbiddenChanges();

    if ($this->hasChanges()) {
      $this->setNewRevision(TRUE);
    }

    if ($this->isNew() || $this->isNewRevision()) {
      $this->set('registered', \Drupal::time()->getRequestTime());
      $this->set('uid', \Drupal::currentUser()->id());
      $this->set('revision_uid', \Drupal::currentUser()->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    /** @var \Drupal\simple_school_reports_grade_support\Service\GradeSnapshotServiceInterface $grade_snapshot_service */
    $grade_snapshot_service = \Drupal::service('simple_school_reports_grade_support.grade_snapshot_service');

    if (!$update || $this->get('correction_type')->value === self::CORRECTION_TYPE_CHANGED) {
      $syllabus = $this->get('syllabus')->entity;
      $school_type_versioned = $syllabus?->get('school_type_versioned')->value;
      if (!$school_type_versioned) {
        throw new \RuntimeException('Failed to resolve school type versions from syllabus: ' . $this->get('syllabus')->target_id);
      }
      $grade_snapshot_service->makeSnapshot($this->get('student')->target_id, [$school_type_versioned]);
      return;
    }

    $new_revision_id = $this->getRevisionId();
    $old_revision_id = $this->original->getRevisionId();

    if (!$new_revision_id || !$old_revision_id) {
      throw new \RuntimeException('New revision ID or old revision ID is not set.');
    }
    $student_id = $this->get('student')->target_id;
    $grade_snapshot_service->updateSnapshotsForGrade($old_revision_id, $new_revision_id, $student_id ?? '*');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    $tags[] = 'ssr_grade_list:student:' . $this->get('student')->target_id;;
    $tags[] = 'ssr_grade_list:syllabus:' . $this->get('syllabus')->target_id;;
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(FALSE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(FALSE)
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('Registered by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setRevisionable(TRUE)
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the grade was last edited.'));

    $fields['identifier'] = BaseFieldDefinition::create('string')
      ->setRevisionable(FALSE)
      ->setLabel(t('Identifier'))
      ->addConstraint('UniqueField')
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['student'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(FALSE)
      ->setRequired(TRUE)
      ->setLabel(t('Student'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['syllabus'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(FALSE)
      ->setRequired(TRUE)
      ->setLabel(t('Syllabus'))
      ->setSetting('target_type', 'ssr_syllabus')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['main_grader'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setLabel(t('Grading teacher'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['joint_grading_by'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setLabel(t('Joint grading by'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['course'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setLabel(t('Course'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['course' => 'course']])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['registered'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Registered date'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['term_index'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Term index'))
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['grade'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Grade'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'af_grade_system' => 'af_grade_system',
          'geg_grade_system' => 'geg_grade_system',
        ],
        'auto_create' => FALSE,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['exclude_reason'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Reason for exclusion'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_grade_support_exclude_reason_options')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['final_grade'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Final grade'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trial'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Grade set from a trial'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['remark'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Remark'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['correction_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Correction type'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_grade_support_correction_types')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['diploma_project_label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Diploma project label'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['diploma_project_description'] = BaseFieldDefinition::create('text_long')
      ->setRevisionable(TRUE)
      ->setLabel(t('Diploma project description'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
