<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simple_school_reports_dnp_support\DnpProvisioningConstantsInterface;
use Drupal\simple_school_reports_dnp_support\DnpProvTestSettingsInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the dnp provisioning test settings entity class.
 *
 * @ContentEntityType(
 *   id = "dnp_prov_test_settings",
 *   label = @Translation("Dnp Provisioning Test Settings"),
 *   label_collection = @Translation("Dnp Provisioning Test Settingss"),
 *   label_singular = @Translation("dnp provisioning test settings"),
 *   label_plural = @Translation("dnp provisioning test settingss"),
 *   label_count = @PluralTranslation(
 *     singular = "@count dnp provisioning test settingss",
 *     plural = "@count dnp provisioning test settingss",
 *   ),
 *   bundle_label = @Translation("Dnp Provisioning Test Settings type"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_dnp_support\DnpProvTestSettingsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_dnp_support\DnpProvTestSettingsAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_dnp_support\Form\DnpProvTestSettingsForm",
 *       "edit" = "Drupal\simple_school_reports_dnp_support\Form\DnpProvTestSettingsForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "dnp_prov_test_settings",
 *   data_table = "dnp_prov_test_settings_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer dnp_prov_test_settings types",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/dnp-prov-test-settings",
 *     "add-form" = "/dnp/provisioning-test-settings/add/{dnp_prov_test_settings_type}",
 *     "add-page" = "/dnp/provisioning-test-settings/add",
 *     "canonical" = "/dnp/provisioning-test-settings/{dnp_prov_test_settings}",
 *     "edit-form" = "/dnp/provisioning-test-settings/{dnp_prov_test_settings}/edit",
 *     "delete-form" = "/dnp/provisioning-test-settings/{dnp_prov_test_settings}/delete",
 *     "delete-multiple-form" = "/admin/content/dnp-prov-test-settings/delete-multiple",
 *   },
 *   bundle_entity_type = "dnp_prov_test_settings_type",
 *   field_ui_base_route = "entity.dnp_prov_test_settings_type.edit_form",
 * )
 */
final class DnpProvTestSettings extends ContentEntityBase implements DnpProvTestSettingsInterface {

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
    $this->set('label', $this->label());
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = '-';

    $test_value = $this->get('test')->value;
    if ($test_value) {
      $label = simple_school_reports_dnp_support_allowed_tests()[$test_value] ?? '-';
    }
    return $label;
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['test'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Test', [], ['context' => 'assessment']))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_dnp_support_allowed_tests')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['teachers'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Teachers for assessment'))
      ->setSetting('target_type', 'user')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['students'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Students'))
      ->setDescription(t('Students not relevant for the selected test will be ignored.'))
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

    $fields['list_behavior'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Usage of the students list'))
      ->setDescription(t('Select if the list above is a list of students to include (explicitly) or exclude from the list of students in the grade relevant for the selected test.'))
      ->setDefaultValue(DnpProvisioningConstantsInterface::DNP_LIST_EXCLUDE)
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_dnp_support_list_behavior_options')
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
      ->setDescription(t('The time that the dnp_prov_test_settings was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the dnp_prov_test_settings was last edited.'));

    return $fields;
  }

}
