<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_module_info;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the module info entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ModuleInfoAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view ssr_module_info'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit ssr_module_info'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete ssr_module_info'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_module_info', 'administer ssr_module_info'], 'OR');
  }

  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    if ($operation !== 'view') {
      return parent::checkFieldAccess($operation, $field_definition, $account, $items);
    }

    if ($field_definition->getName() === 'price' || $field_definition->getName() === 'annual_fee') {
      $hide_price_info = \Drupal::state()->get('ssr_module_info.hide_price_info', FALSE);
      if ($hide_price_info) {
        return AccessResult::forbidden()->addCacheTags(['ssr_module_info.settings']);
      }
    }


    if ($field_definition->getName() === 'price') {
      $enabled = $items?->getEntity()?->get('enabled')->value ??  FALSE;
      if ($enabled) {
        return AccessResult::forbidden()->addCacheTags(['ssr_module_info.settings']);
      }
      return AccessResult::allowedIfHasPermission($account, 'view ssr_module_info price')->addCacheTags(['ssr_module_info.settings']);
    }

    if ($field_definition->getName() === 'annual_fee') {
      return AccessResult::allowedIfHasPermission($account, 'view ssr_module_info price')->addCacheTags(['ssr_module_info.settings']);
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
