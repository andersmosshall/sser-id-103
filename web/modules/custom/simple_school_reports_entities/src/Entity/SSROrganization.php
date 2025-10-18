<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_core\SchoolGradeHelper;
use Drupal\simple_school_reports_entities\SSROrganizationInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the ssr organization entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_organization",
 *   label = @Translation("SSR Organization"),
 *   label_collection = @Translation("SSR Organizations"),
 *   label_singular = @Translation("ssr organization"),
 *   label_plural = @Translation("ssr organizations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ssr organizations",
 *     plural = "@count ssr organizations",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_entities\SSROrganizationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_entities\SSROrganizationAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\SSROrganizationForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\SSROrganizationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_organization",
 *   admin_permission = "administer ssr_organization",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-organization",
 *     "add-form" = "/ssr-organization/add",
 *     "canonical" = "/ssr-organization/{ssr_organization}",
 *     "edit-form" = "/ssr-organization/{ssr_organization}/edit",
 *     "delete-form" = "/ssr-organization/{ssr_organization}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-organization/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_organization.settings",
 *   constraints = {
 *     "SsrOrganizationConstraint" = {}
 *   }
 * )
 */
final class SSROrganization extends ContentEntityBase implements SSROrganizationInterface {

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

    // School types/grades are only relevant for school unit organizations.
    if ($this->get('organization_type')->value !== 'school_unit') {
      $this->set('school_types', NULL);
    }

    // School unit has to have a parent of type school.
    if ($this->get('organization_type')->value === 'school_unit' && ($this->get('parent')->isEmpty() || $this->get('parent')->entity?->get('organization_type')->value !== 'school')) {
      throw new \RuntimeException('A school unit has to have a parent organization of type school.');
    }
    // School has to have a parent of type school organiser.
    if ($this->get('organization_type')->value === 'school' && ($this->get('parent')->isEmpty() || $this->get('parent')->entity?->get('organization_type')->value !== 'school_organiser')) {
      throw new \RuntimeException('A school has to have a parent organization of type school organiser.');
    }

    $sort_index = 900;
    if ($this->get('organization_type')->value === 'school_organiser') {
      $sort_index = 100;
    }
    elseif ($this->get('organization_type')->value === 'school') {
      $sort_index = 200;
    }
    elseif ($this->get('organization_type')->value === 'school_unit') {
      $sort_index = 300;
    }
    $this->set('sort_index', $sort_index);

    $filtered_school_grades = [];

    if ($this->get('organization_type')->value === 'school_unit') {
      $selected_school_grades = array_column($this->get('school_grades')->getValue(), 'value');
      $selected_school_types = array_column($this->get('school_types')->getValue(), 'value');

      $supported_grades = array_keys(SchoolGradeHelper::getSupportedSchoolGrades($selected_school_types));
      foreach ($selected_school_grades as $school_grade) {
        if (in_array($school_grade, $supported_grades)) {
          $filtered_school_grades[] = $school_grade;
        }
      }
    }

    $this->set('school_grades', $filtered_school_grades);
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

    $fields['short_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Short label'))
      ->setDescription(t('Short label, recomended 3 characters.'))
      ->setSetting('max_length', 5)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent organization'))
      ->setSetting('target_type', 'ssr_organization')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['organization_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Organization type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_organization_types')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['organization_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Organization number'))
      ->setSetting('max_length', 32)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_unit_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('School unit code'))
      ->setSetting('max_length', 32)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_types'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('School types'))
      ->setDescription(t('The school type.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_school_types')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['school_grades'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('School grades'))
      ->setDescription(t('Active school grades.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_school_grades')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['municipality'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Municipality'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['municipality_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Municipality code'))
      ->setSetting('max_length', 32)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone number'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the ssr organization was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the ssr organization was last edited.'));

    $fields['sort_index'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Sort index'))
      ->setRequired(TRUE);

    return $fields;
  }

}
