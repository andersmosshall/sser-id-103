<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the child care entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ChildCareAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($operation === 'check_in_out') {
      if ($account->hasPermission('administer simple school reports settings')) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      return $this->allowIfTeacher($entity, $account);
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view ssr_child_care'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit ssr_child_care')->orIf($this->allowIfTeacher($entity, $account)),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete ssr_child_care'),
      default => AccessResult::neutral(),
    };
  }

  protected function allowIfTeacher(EntityInterface $entity, AccountInterface $account): AccessResult {
    $teachers = array_column($entity->get('teachers')->getValue(), 'target_id');
    return AccessResult::allowedIf(in_array($account->id(), $teachers))->addCacheableDependency($entity)->cachePerUser();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_child_care', 'administer ssr_child_care types'], 'OR');
  }

}
