<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the assessment group entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_assessment_group",
 *   label = @Translation("Assessment group"),
 *   label_collection = @Translation("Assessment groups"),
 *   label_singular = @Translation("assessment group"),
 *   label_plural = @Translation("assessment groups"),
 *   label_count = @PluralTranslation(
 *     singular = "@count assessment groups",
 *     plural = "@count assessment groups",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_examinations_support\AssessmentGroupListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_examinations_support\AssessmentGroupAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_examinations_support\Form\AssessmentGroupForm",
 *       "edit" = "Drupal\simple_school_reports_examinations_support\Form\AssessmentGroupForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_assessment_group",
 *   data_table = "ssr_assessment_group_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_assessment_group",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/assessment-group",
 *     "add-form" = "/assessment-group/add",
 *     "canonical" = "/assessment-group/{ssr_assessment_group}",
 *     "edit-form" = "/assessment-group/{ssr_assessment_group}/edit",
 *     "delete-form" = "/assessment-group/{ssr_assessment_group}/delete",
 *     "delete-multiple-form" = "/admin/content/assessment-group/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_assessment_group.settings",
 * )
 */
final class AssessmentGroup extends ContentEntityBase implements AssessmentGroupInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  protected array|null $secondaryPermissionsMap = NULL;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  protected function getSecondaryPermissionsMap(): array {
    if (is_array($this->secondaryPermissionsMap)) {
      return $this->secondaryPermissionsMap;
    }
    /** @var \Drupal\simple_school_reports_examinations_support\AssessmentGroupUserInterface[] $assessment_group_users */
    $assessment_group_users = $this->get('other_teachers')->referencedEntities();
    $this->secondaryPermissionsMap = [];
    foreach ($assessment_group_users as $assessment_group_user) {
      $uids = array_column($assessment_group_user->get('teachers')->getValue(), 'target_id');

      $permissions = [];
      foreach (self::ALL_PERMISSIONS as $permission) {
        if ($assessment_group_user->hasField($permission) && $assessment_group_user->get($permission)->value) {
          $permissions[] = $permission;
        }
      }

      foreach ($uids as $uid) {
        foreach ($permissions as $permission) {
          $this->secondaryPermissionsMap[$uid][] = $permission;
        }
      }
    }
    // Clean up the map.
    foreach ($this->secondaryPermissionsMap as $uid => $permissions) {
      $this->secondaryPermissionsMap[$uid] = array_unique($permissions);
    }
    return $this->secondaryPermissionsMap;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions(string $uid): array {
    if (!$uid) {
      return [];
    }

    $main_teacher = $this->get('main_teacher')->target_id ?? '';
    if ($main_teacher == $uid) {
      return self::ALL_PERMISSIONS;
    }

    return $this->getSecondaryPermissionsMap()[$uid] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission(string $uid, string $permission): bool {
    $user_permissions = $this->getPermissions($uid);
    return in_array($permission, $user_permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnyPermissions(string $uid, array $permissions): bool {
    $user_permissions = $this->getPermissions($uid);
    return !empty(array_intersect($permissions, $user_permissions));
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Subject'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', [
        'target_bundles' => ['school_subject' => 'school_subject'],
        'auto_create' => TRUE,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['main_teacher'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Main teacher'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler_settings', [
        'filter' => [
          'type' => 'role',
          'role' => [
            'teacher' => 'teacher',
            'administrator' => 'administrator',
            'principle' => 'principle',
          ],
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['other_teachers'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Permissions for others'))
      ->setSetting('target_type', 'ssr_assessment_group_user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_class'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Class'))
      ->setDescription(t('All students from selected class will be assigned to this course. Leave blank to select an explicit student list below.'))
      ->setSetting('target_type', 'ssr_school_class')
      ->setSetting('handler', 'views')
      ->setSetting('handler_settings', [
        'view' => [
          'arguments' => [],
          'display_name' => 'entity_reference_1',
          'view_name' => 'class_reference',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['students'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Students'))
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Created by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the assessment group was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the assessment group was last edited.'));


    return $fields;
  }

}
