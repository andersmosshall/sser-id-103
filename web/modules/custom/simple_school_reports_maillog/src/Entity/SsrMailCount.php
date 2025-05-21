<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_maillog\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simple_school_reports_maillog\SsrMailCountInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the mail count entity class.
 *
 * @ContentEntityType(
 *   id = "ssr_mail_count",
 *   label = @Translation("Mail count"),
 *   label_collection = @Translation("Mail counts"),
 *   label_singular = @Translation("mail count"),
 *   label_plural = @Translation("mail counts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count mail counts",
 *     plural = "@count mail counts",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_school_reports_maillog\SsrMailCountListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simple_school_reports_maillog\SsrMailCountAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\simple_school_reports_maillog\Form\SsrMailCountForm",
 *       "edit" = "Drupal\simple_school_reports_maillog\Form\SsrMailCountForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ssr_mail_count",
 *   admin_permission = "administer ssr_mail_count",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ssr-mail-count",
 *     "add-form" = "/ssr-mail-count/add",
 *     "canonical" = "/ssr-mail-count/{ssr_mail_count}",
 *     "edit-form" = "/ssr-mail-count/{ssr_mail_count}/edit",
 *     "delete-form" = "/ssr-mail-count/{ssr_mail_count}/delete",
 *     "delete-multiple-form" = "/admin/content/ssr-mail-count/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.ssr_mail_count.settings",
 * )
 */
final class SsrMailCount extends ContentEntityBase implements SsrMailCountInterface {

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
      ->setLabel(t('Label'))
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
      ->setDescription(t('The time that the mail count was last edited.'));

    $fields['from'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('From'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['to'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('To'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Sent'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['failed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Failed'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['simulated'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Simulated'))
      ->setDefaultValue(0)
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
