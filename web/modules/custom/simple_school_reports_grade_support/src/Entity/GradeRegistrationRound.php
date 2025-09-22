<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_grade_support\GradeRegistrationRoundInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the grade registration round entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_grade_reg_round",
 *   label = @Translation("Grade registration round"),
 *   label_collection = @Translation("Grade registration rounds"),
 *   label_singular = @Translation("grade registration round"),
 *   label_plural = @Translation("grade registration rounds"),
 *   label_count = @PluralTranslation(
 *     singular = "@count grade registration rounds",
 *     plural = "@count grade registration rounds",
 *   ),
 *   bundle_label = @Translation("Grade registration round type"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_grade_support\GradeRegistrationRoundListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_grade_support\GradeRegistrationRoundAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_grade_support\Form\GradeRegistrationRoundForm",
 *       "edit" = "Drupal\simple_school_reports_grade_support\Form\GradeRegistrationRoundForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_grade_reg_round",
 *   data_table = "ssr_grade_reg_round_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_grade_reg_round types",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-grade-registration-round",
 *     "add-form" = "/grade-registration/round/add/{ssr_grade_reg_round_type}",
 *     "add-page" = "/grade-registration/round/add",
 *     "canonical" = "/grade-registration/round/{ssr_grade_reg_round}",
 *     "edit-form" = "/grade-registration/round/{ssr_grade_reg_round}/edit",
 *     "delete-form" = "/grade-registration/round/{ssr_grade_reg_round}/delete",
 *     "delete-multiple-form" = "/admin/content/grade-registration/round/delete-multiple",
 *   },
 *   bundle_entity_type = "ssr_grade_reg_round_type",
 *   field_ui_base_route = "entity.ssr_grade_reg_round_type.edit_form",
 * )
 */
final class GradeRegistrationRound extends ContentEntityBase implements GradeRegistrationRoundInterface {

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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDefaultValue(FALSE)
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the grade was last edited.'));

    $fields['open'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Open for grade registrations'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
