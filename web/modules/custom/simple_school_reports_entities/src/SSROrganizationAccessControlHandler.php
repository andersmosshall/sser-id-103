<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the ssr organization entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class SSROrganizationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view ssr_organization'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit ssr_organization'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete ssr_organization')->andIf(AccessResult::allowedIf($entity->get('organization_type')->value === 'school_unit')),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_organization', 'administer ssr_organization'], 'OR');
  }

}
