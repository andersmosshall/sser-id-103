<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_examinations_support\ExaminationInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the examination entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_examination",
 *   label = @Translation("Examination"),
 *   label_collection = @Translation("Examinations"),
 *   label_singular = @Translation("examination"),
 *   label_plural = @Translation("examinations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count examinations",
 *     plural = "@count examinations",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_examinations_support\ExaminationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_examinations_support\ExaminationAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_examinations_support\Form\ExaminationForm",
 *       "edit" = "Drupal\simple_school_reports_examinations_support\Form\ExaminationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_examination",
 *   data_table = "ssr_examination_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_examination",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-examination",
 *     "add-form" = "/ssr-examination/add",
 *     "canonical" = "/ssr-examination/{ssr_examination}",
 *     "edit-form" = "/ssr-examination/{ssr_examination}/edit",
 *     "delete-form" = "/ssr-examination/{ssr_examination}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-examination/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_examination.settings",
 * )
 */
final class Examination extends ContentEntityBase implements ExaminationInterface {

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

    $fields['assessment_group'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Assessment group'))
      ->setSetting('target_type', 'ssr_assessment_group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['deadline'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last date'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDescription(t('If not published no examination result will be shown on the student tab.'))
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
      ->setDescription(t('The time that the examination was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the examination was last edited.'));

    return $fields;
  }

}
