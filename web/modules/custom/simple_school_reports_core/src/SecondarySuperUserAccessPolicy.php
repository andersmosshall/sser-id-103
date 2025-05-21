<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_core;

use Drupal\Core\Session\AccessPolicyBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissionsInterface;
use Drupal\Core\Site\Settings;

/**
 * Grants super admin an all access pass.
 */
final class SecondarySuperUserAccessPolicy extends AccessPolicyBase {

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): RefinableCalculatedPermissionsInterface {
    $calculated_permissions = parent::calculatePermissions($account, $scope);

    if (!in_array('super_admin', $account->getRoles())) {
      return $calculated_permissions;
    }

    $allowed_super_admins = (int) Settings::get('ssr_allowed_super_admins', 0);
    if ($allowed_super_admins <= 0) {
      return $calculated_permissions;
    }

    return $calculated_permissions->addItem(new CalculatedPermissionsItem([], TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts(): array {
    return ['user.roles:super_admin'];
  }

}
