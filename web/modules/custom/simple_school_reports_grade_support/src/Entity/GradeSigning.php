<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_grade_support\GradeSigningInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the gradesigning entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_grade_signing",
 *   label = @Translation("GradeSigning"),
 *   label_collection = @Translation("Grade signings"),
 *   label_singular = @Translation("gradesigning"),
 *   label_plural = @Translation("grade signings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count grade signings",
 *     plural = "@count grade signings",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_grade_support\GradeSigningListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_grade_support\GradeSigningAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_grade_support\Form\GradeSigningForm",
 *       "edit" = "Drupal\simple_school_reports_grade_support\Form\GradeSigningForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_grade_signing",
 *   admin_permission = "administer ssr_grade_signing",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-grade-signing",
 *     "add-form" = "/ssr-grade-signing/add",
 *     "canonical" = "/ssr-grade-signing/{ssr_grade_signing}",
 *     "edit-form" = "/ssr-grade-signing/{ssr_grade_signing}/edit",
 *     "delete-form" = "/ssr-grade-signing/{ssr_grade_signing}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-grade-signing/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_grade_signing.settings",
 * )
 */
final class GradeSigning extends ContentEntityBase implements GradeSigningInterface {

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

  public function isSigned(): bool {
    return !!$this->get('signing')->target_id;
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

    $fields['signing'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Signing'))
      ->setSetting('target_type', 'ssr_signing')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['grades'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Grades'))
      ->setSetting('target_type', 'ssr_grade')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
