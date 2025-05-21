<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_module_info\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simple_school_reports_module_info\ModuleInfoInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the module info entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_module_info",
 *   label = @Translation("Module info"),
 *   label_collection = @Translation("Module infos"),
 *   label_singular = @Translation("module info"),
 *   label_plural = @Translation("module infos"),
 *   label_count = @PluralTranslation(
 *     singular = "@count module infos",
 *     plural = "@count module infos",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_module_info\ModuleInfoListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_module_info\ModuleInfoAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_module_info\Form\ModuleInfoForm",
 *       "edit" = "Drupal\simple_school_reports_module_info\Form\ModuleInfoForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_module_info",
 *   data_table = "ssr_module_info_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer ssr_module_info",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-module-info",
 *     "add-form" = "/ssr-module-info/add",
 *     "canonical" = "/ssr-module-info/{ssr_module_info}",
 *     "edit-form" = "/ssr-module-info/{ssr_module_info}/edit",
 *     "delete-form" = "/ssr-module-info/{ssr_module_info}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-module-info/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_module_info.settings",
 * )
 */
final class ModuleInfo extends ContentEntityBase implements ModuleInfoInterface {

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
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the module info was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the module info was last edited.'));

    $fields['module'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Module'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_module_info_get_modules')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['module_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Module type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_module_info_get_module_types')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['required_modules'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Required modules'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values_function', 'simple_school_reports_module_info_get_modules')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recommended_modules'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Recommended modules'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setSetting('allowed_values_function', 'simple_school_reports_module_info_get_modules')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', t('Enabled'))
      ->setSetting('off_label', t('Not enabled'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Price'))
      ->setSetting('max_length', 255)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['annual_fee'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Annual fee'))
      ->setSetting('max_length', 255)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
      ])
      ->setSetting('third_party_settings', [
        'allowed_formats' => [
          'full_html',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['help_pages'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Help pages'))
      ->setSetting('target_type', 'node')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ssr_demo_link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Demonstration link'))
      ->setSetting('max_length', 3000)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
