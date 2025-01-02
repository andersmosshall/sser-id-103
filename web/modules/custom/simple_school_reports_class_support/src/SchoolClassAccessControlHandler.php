<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_class_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the school class entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class SchoolClassAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($operation === 'delete' && $entity->id()) {
      /** @var \Drupal\simple_school_reports_class_support\Service\SsrClassServiceInterface $class_service */
      $class_service = \Drupal::service('simple_school_reports_class_support.class_service');
      if (!empty($class_service->getStudentIdsByClassId($entity->id()))) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
    }

    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view ssr_school_class'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit ssr_school_class'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete ssr_school_class'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_school_class', 'administer ssr_school_class'], 'OR');
  }

}
