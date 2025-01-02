<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the dnp provisioning settings entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class DnpProvSettingsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view dnp_prov_settings'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit dnp_prov_settings'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete dnp_prov_settings'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    if (!empty(\Drupal::entityTypeManager()->getStorage('dnp_prov_settings')->getQuery()->accessCheck(FALSE)->condition('status', TRUE)->execute())) {
      return AccessResult::forbidden('Only one DNP provisioning settings entity can be created.')->addCacheTags(['dnp_prov_settings_list']);
    }

    return AccessResult::allowedIfHasPermissions($account, ['create dnp_prov_settings', 'administer dnp_prov_settings'], 'OR')->addCacheTags(['dnp_prov_settings_list']);
  }

}
