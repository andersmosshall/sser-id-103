<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_grade_support\GradeSnapshotInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the grade snapshot entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_grade_snapshot",
 *   label = @Translation("Grade snapshot"),
 *   label_collection = @Translation("Grade snapshots"),
 *   label_singular = @Translation("grade snapshot"),
 *   label_plural = @Translation("grade snapshots"),
 *   label_count = @PluralTranslation(
 *     singular = "@count grade snapshots",
 *     plural = "@count grade snapshots",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_grade_support\GradeSnapshotListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_grade_support\GradeSnapshotAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_grade_support\Form\GradeSnapshotForm",
 *       "edit" = "Drupal\simple_school_reports_grade_support\Form\GradeSnapshotForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_grade_snapshot",
 *   admin_permission = "administer ssr_grade_snapshot",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-grade-snapshot",
 *     "add-form" = "/ssr-grade-snapshot/add",
 *     "canonical" = "/ssr-grade-snapshot/{ssr_grade_snapshot}",
 *     "edit-form" = "/ssr-grade-snapshot/{ssr_grade_snapshot}/edit",
 *     "delete-form" = "/ssr-grade-snapshot/{ssr_grade_snapshot}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-grade-snapshot/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_grade_snapshot.settings",
 * )
 */
final class GradeSnapshot extends ContentEntityBase implements GradeSnapshotInterface {

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

    if ($this->get('grade_snapshot_period')->isEmpty()) {
      throw new \RuntimeException('Grade snapshot period must be set.');
    }

    if ($this->get('student')->isEmpty()) {
      throw new \RuntimeException('Student must be set.');
    }

    /** @var \Drupal\simple_school_reports_grade_support\Service\GradeSnapshotServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_grade_support.grade_snapshot_service');
    $identifier = $service->makeSnapshotIdentifier($this->get('grade_snapshot_period')->target_id, $this->get('student')->target_id);
    $this->set('identifier', $identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    $tags[] = 'ssr_grade_snapshot_list:student:' . $this->get('student')->target_id;;
    return $tags;
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

    $fields['identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Identifier'))
      ->addConstraint('UniqueField')
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['grade_snapshot_period'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Period'))
      ->setSetting('target_type', 'ssr_grade_snapshot_period')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['student'] = BaseFieldDefinition::create('entity_reference')
      ->setRequired(TRUE)
      ->setLabel(t('Student'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_grade'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('School grade'))
      ->setDescription(t('The school grade of the student at the time of the snapshot.'))
      ->setSetting('allowed_values_function', 'simple_school_reports_core_allowed_user_grade')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['gender'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Gender'))
      ->setRevisionable(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_core_gender_options')
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
