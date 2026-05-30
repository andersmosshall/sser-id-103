<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the child care schema deviation entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ChildCareSchemaDeviationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    /** @var \Drupal\simple_school_reports_child_care_support\ChildCareSchemaDeviationInterface $entity */
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    /** @var \Drupal\Core\Entity\EntityInterface | null $parent */
    $parent = $entity->get('child_care')->entity;
    $parent_view = $parent ? $parent->access('view', $account, TRUE) : AccessResult::forbidden();
    $update_parent = $parent ? $parent->access('update', $account, TRUE) : AccessResult::forbidden();

    $is_future = AccessResult::allowedIf($entity->isFuture())->addCacheableDependency($entity);
    // Recalculate in 12 hours if not allowed.
    if ($is_future->isAllowed()) {
      $is_future->setCacheMaxAge(12 * 60 * 60);
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view ssr_cc_deviation')
        ->orIf($parent_view),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit ssr_cc_deviation')
        ->orIf($update_parent->andIf($is_future)),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete ssr_cc_deviation')
        ->orIf($update_parent->andIf($is_future)),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_cc_deviation', 'administer ssr_cc_deviation'], 'OR');
  }

}
