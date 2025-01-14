<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simple_school_reports_examinations_support\AssessmentGroupUserInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the assessment group user entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_assessment_group_user",
 *   label = @Translation("Assessment group user"),
 *   label_collection = @Translation("Assessment group users"),
 *   label_singular = @Translation("assessment group user"),
 *   label_plural = @Translation("assessment group users"),
 *   label_count = @PluralTranslation(
 *     singular = "@count assessment group users",
 *     plural = "@count assessment group users",
 *   ),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\simple_school_reports_examinations_support\AssessmentGroupUserListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" =
 *   "Drupal\simple_school_reports_examinations_support\AssessmentGroupUserAccessControlHandler",
 *     "form" = {
 *       "add" =
 *   "Drupal\simple_school_reports_examinations_support\Form\AssessmentGroupUserForm",
 *       "edit" =
 *   "Drupal\simple_school_reports_examinations_support\Form\AssessmentGroupUserForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" =
 *   "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" =
 *   "Drupal\simple_school_reports_examinations_support\Routing\AssessmentGroupUserHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_assessment_group_user",
 *   admin_permission = "administer ssr_assessment_group_user",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-assessment-group-user",
 *     "add-form" = "/ssr-assessment-group-user/add",
 *     "canonical" = "/ssr-assessment-group-user/{ssr_assessment_group_user}",
 *     "edit-form" = "/ssr-assessment-group-user/{ssr_assessment_group_user}",
 *     "delete-form" =
 *   "/ssr-assessment-group-user/{ssr_assessment_group_user}/delete",
 *     "delete-multiple-form" =
 *   "/admin/content/ssr-assessment-group-user/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_assessment_group_user.settings",
 * )
 */
final class AssessmentGroupUser extends ContentEntityBase implements AssessmentGroupUserInterface {

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
  }

  public function label() {
    $teachers = $this->get('teachers')->referencedEntities();
    $teacher_names = [];
    foreach ($teachers as $teacher) {
      $teacher_names[] = $teacher->getDisplayName();
    }
    return implode(', ', $teacher_names);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['teachers'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setLabel(t('Teachers'))
      ->setSetting('target_type', 'user')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
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

    $fields['administer_assessment_group'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Allow administer this assessment group'))
      ->setDescription(t('To be able to change the settings of this assessment group.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['view_examination_results'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Allow view all examination results'))
      ->setDescription(t('To be able to view all examination results in this assessment group.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['edit_examination_results'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Allow handle all examination results'))
      ->setDescription(t('To be able to handle all examination results in this assessment group.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['add_examinations'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Allow add examinations'))
      ->setDescription(t('To be able to add new examination results in this assessment group.'))
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
      ->setDescription(t('The time that the assessment group user was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the assessment group user was last edited.'));

    return $fields;
  }

}
