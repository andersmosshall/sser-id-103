<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simple_school_reports_examinations_support\ExaminationResultInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the examination result entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_examination_result",
 *   label = @Translation("Examination result"),
 *   label_collection = @Translation("Examination results"),
 *   label_singular = @Translation("examination result"),
 *   label_plural = @Translation("examination results"),
 *   label_count = @PluralTranslation(
 *     singular = "@count examination results",
 *     plural = "@count examination results",
 *   ),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\simple_school_reports_examinations_support\ExaminationResultListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" =
 *   "Drupal\simple_school_reports_examinations_support\ExaminationResultAccessControlHandler",
 *     "form" = {
 *       "add" =
 *   "Drupal\simple_school_reports_examinations_support\Form\ExaminationResultForm",
 *       "edit" =
 *   "Drupal\simple_school_reports_examinations_support\Form\ExaminationResultForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" =
 *   "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_examination_result",
 *   data_table = "ssr_examination_result_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_examination_result",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-examination-result",
 *     "add-form" = "/ssr-examination-result/add",
 *     "canonical" = "/ssr-examination-result/{ssr_examination_result}",
 *     "edit-form" = "/ssr-examination-result/{ssr_examination_result}/edit",
 *     "delete-form" =
 *   "/ssr-examination-result/{ssr_examination_result}/delete",
 *     "delete-multiple-form" =
 *   "/admin/content/ssr-examination-result/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_examination_result.settings",
 * )
 */
final class ExaminationResult extends ContentEntityBase implements ExaminationResultInterface {

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

  public function getListCacheTagsToInvalidate() {
    $tags = parent::getListCacheTagsToInvalidate();

    /** @var \Drupal\simple_school_reports_examinations_support\ExaminationInterface $examination */
    $examination = $this->get('examination')->entity;
    if ($examination) {
      $tags[] = 'ssr_examination_list:e:' . $examination->id();

      $assessment_group_id = $examination->get('assessment_group')->target_id;
      if ($assessment_group_id) {
        $tags[] = 'ssr_assessment_group_list:ag:' . $assessment_group_id;
      }
    }

    $student_uid = $this->get('student')->target_id;
    if ($student_uid) {
      $tags[] = 'ssr_examination_result_list:u:' . $student_uid;
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['examination'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Examination'))
      ->setSetting('target_type', 'ssr_examination')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['student'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setLabel(t('Student'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('State', [], ['context' => 'ssr']))
      ->setSetting('allowed_values_function', 'assessment_group_user_examination_result_state_options')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDescription('If not published this examination result will be shown on the student tab.')
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
      ->setDescription(t('The time that the examination result was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the examination result was last edited.'));

    return $fields;
  }

}
