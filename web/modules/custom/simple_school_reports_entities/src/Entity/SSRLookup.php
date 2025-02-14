<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simple_school_reports_entities\SSRLookupInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the ssr lookup entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_lookup",
 *   label = @Translation("SSR Lookup"),
 *   label_collection = @Translation("SSR Lookups"),
 *   label_singular = @Translation("ssr lookup"),
 *   label_plural = @Translation("ssr lookups"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ssr lookups",
 *     plural = "@count ssr lookups",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_entities\SSRLookupListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_entities\SSRLookupAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_entities\Form\SSRLookupForm",
 *       "edit" = "Drupal\simple_school_reports_entities\Form\SSRLookupForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_lookup",
 *   admin_permission = "administer ssr_lookup",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-lookup",
 *     "add-form" = "/ssr-lookup/add",
 *     "canonical" = "/ssr-lookup/{ssr_lookup}",
 *     "edit-form" = "/ssr-lookup/{ssr_lookup}/edit",
 *     "delete-form" = "/ssr-lookup/{ssr_lookup}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-lookup/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_lookup.settings",
 * )
 */
final class SSRLookup extends ContentEntityBase implements SSRLookupInterface {

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

    if (!$this->label()) {
      $this->set('label', 'Default lookup');
    }

    if (!$this->get('expires')->value) {
      $this->set('expires', strtotime('+3 years'));
    }
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

    $fields['identifier'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Identifier'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 1024)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['meta'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Meta data in JSON format'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Lookup type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', 'simple_school_reports_entities_lookup_types')
      ->setDefaultValue('default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
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
      ->setDescription(t('The time that the ssr lookup was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the ssr lookup was last edited.'));

    return $fields;
  }

}
