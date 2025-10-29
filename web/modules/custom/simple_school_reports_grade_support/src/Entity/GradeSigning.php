<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_grade_support\GradeSigningInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the grade signing entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_grade_signing",
 *   label = @Translation("Grade signing"),
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
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }

    $signing_complete = !empty($this->get('signing')->target_id);
    $this->set('signing_complete', $signing_complete);
  }

  /**
   * {@inheritdoc}
   */
  public function isSigned(): bool {
    return !!$this->get('signing')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentId(): string {
    $id = $this->id();
    if (!$id) {
      throw new \RuntimeException('Cannot get document id for an unsaved grade signing.');
    }

    $ssr_id = Settings::get('ssr_id', '');
    if ($ssr_id === '') {
      throw new \RuntimeException('Failed to calculate document id, missing ssr id.');
    }

    return 'BS-' . $ssr_id . '-' . format_with_leading_zeros($id, 6);
  }

  public function getShortSummary(): string {
    $signees = $this->get('signees')->referencedEntities();
    $summary_suffix = '';
    if (!empty($signees)) {
      $summary_suffix = ' ' . $this->t('Signed by @signees', ['@signees' => implode(', ', array_map(fn(UserInterface $user) => $user->getDisplayName(), $signees))]);
    }
    return $this->getDocumentId() . $summary_suffix;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
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

    $fields['signees'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('To sign'))
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['signing_complete'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Signing complete'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Yes'))
      ->setSetting('on_label', t('No'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['grades'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Grades'))
      ->setSetting('target_type', 'ssr_grade')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['syllabus'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(FALSE)
      ->setRequired(TRUE)
      ->setLabel(t('Syllabus'))
      ->setSetting('target_type', 'ssr_syllabus')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['export_document_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Export document key'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
