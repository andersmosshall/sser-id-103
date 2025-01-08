<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the dnp provisioning entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class DnpProvisioningAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view dnp_provisioning'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit dnp_provisioning'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete dnp_provisioning'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create dnp_provisioning', 'administer dnp_provisioning'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    if ($field_definition->getName() === 'field_src') {
      return AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    }
    if ($field_definition->getName() === 'settings') {
      return AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
