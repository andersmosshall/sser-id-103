<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_api_support;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccessPolicyBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissionsInterface;
use Drupal\Core\Site\Settings;

/**
 * Grants super admin an all access pass.
 */
final class ApiUserAccessPolicy extends AccessPolicyBase {

  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, string $scope): RefinableCalculatedPermissionsInterface {
    // TEMP! This is a work in progress. Disable the API user access policy
    // until we have a proper implementation.
    return parent::calculatePermissions($account, $scope);

    $calculated_permissions = parent::calculatePermissions($account, $scope);

    $uid = $account->id();

//    if (!in_array('api', $account->getRoles())) {
//      return $calculated_permissions;
//    }

    $resources = [];
    if ($this->moduleHandler->moduleExists('simple_school_reports_api_organization')) {
      $resources[] = 'ssr_api_organization_list';
      $resources[] = 'ssr_api_organization';
    }

    if ($this->moduleHandler->moduleExists('simple_school_reports_api_person')) {
      $resources[] = 'ssr_api_person_list';
      $resources[] = 'ssr_api_person';
    }

    $rest_resource_permissions = [];
    foreach ($resources as $resource) {
      foreach (['get', 'post', 'patch', 'delete'] as $method) {
        $rest_resource_permissions[] = "restful $method $resource";
      }
    }

    return $calculated_permissions->addItem(new CalculatedPermissionsItem($rest_resource_permissions));
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts(): array {
    return ['user.roles:api'];
  }

}
