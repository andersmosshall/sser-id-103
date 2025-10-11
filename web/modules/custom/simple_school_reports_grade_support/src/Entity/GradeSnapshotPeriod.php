<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_grade_support\GradeSnapshotPeriodInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the grade snapshot period entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_grade_snapshot_period",
 *   label = @Translation("Grade snapshot period"),
 *   label_collection = @Translation("Grade snapshot periods"),
 *   label_singular = @Translation("grade snapshot period"),
 *   label_plural = @Translation("grade snapshot periods"),
 *   label_count = @PluralTranslation(
 *     singular = "@count grade snapshot periods",
 *     plural = "@count grade snapshot periods",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_grade_support\GradeSnapshotPeriodListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_grade_support\GradeSnapshotPeriodAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_grade_support\Form\GradeSnapshotPeriodForm",
 *       "edit" = "Drupal\simple_school_reports_grade_support\Form\GradeSnapshotPeriodForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_grade_snapshot_period",
 *   data_table = "ssr_grade_snapshot_period_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_grade_snapshot_period",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-grade-snapshot-period",
 *     "add-form" = "/ssr-grade-snapshot-period/add",
 *     "canonical" = "/ssr-grade-snapshot-period/{ssr_grade_snapshot_period}",
 *     "edit-form" = "/ssr-grade-snapshot-period/{ssr_grade_snapshot_period}/edit",
 *     "delete-form" = "/ssr-grade-snapshot-period/{ssr_grade_snapshot_period}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-grade-snapshot-period/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_grade_snapshot_period.settings",
 * )
 */
final class GradeSnapshotPeriod extends ContentEntityBase implements GradeSnapshotPeriodInterface {

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

    if ($this->isNew() && $this->get('period_index')->isEmpty()) {
      /** @var \Drupal\simple_school_reports_core\Service\TermServiceInterface $term_service */
      $term_service = \Drupal::service('simple_school_reports_core.term_service');
      $term_index = $term_service->getDefaultTermIndex();
      $this->set('period_index', $term_index);

      $parsed_term = $term_service->parseDefaultTermIndex($term_index);
      $this->set('label', $parsed_term['semester_name_short']);
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the grade was last edited.'));

    $fields['period_index'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Period index'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_type_versioned'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('School type'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values_function', 'simple_school_reports_core_school_type_versioned_options')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
