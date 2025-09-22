<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the grade entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class GradeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view ssr_grade'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit ssr_grade'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete ssr_grade'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete ssr_grade revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view ssr_grade revision', 'view ssr_grade']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert ssr_grade revision', 'edit ssr_grade']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_grade', 'administer ssr_grade'], 'OR');
  }

}
