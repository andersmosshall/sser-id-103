<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the access control handler for the student leave application entity
 * type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class StudentLeaveApplicationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $cache = new CacheableMetadata();
    $cache->addCacheableDependency($entity);
    $cache->addCacheContexts(['user']);

    if ($operation !== 'handle' && $account->hasPermission('administer ssr_student_leave_application')) {
      return AccessResult::allowed()->addCacheableDependency($cache);
    }

    if ($operation !== 'view') {
      if ($entity->get('state')->value !== 'pending') {
        return AccessResult::forbidden()->addCacheableDependency($cache);
      }
    }

    switch ($operation) {
      case 'view':
        $allow_view_access = AccessResult::forbidden();
        if ($account->hasPermission('school staff permissions')) {
          $allow_view_access = AccessResult::allowed();
        }
        else {
          /** @var \Drupal\user\UserInterface|null $student */
          $student = $entity->get('student')->entity;
          $allow_view_access = $student?->access('update', $account, TRUE) ?? AccessResult::forbidden();
          $cache->addCacheableDependency($allow_view_access);
        }
        $access = AccessResult::allowedIf($allow_view_access->isAllowed())->andIf(AccessResult::allowedIfHasPermission($account, 'view ssr_student_leave_application'));
        return $access->addCacheableDependency($cache);

      case 'update':
      case 'delete':
        /** @var \Drupal\user\UserInterface|null $student */
        $student = $entity->get('student')->entity;

        if (!$student && $operation === 'delete') {
          return AccessResult::allowedIfHasPermission($account, 'school staff permissions');
        }

        $has_caregiver_access = $student?->access('caregiver_access', $account, TRUE) ?? AccessResult::forbidden();
        $cache->addCacheableDependency($has_caregiver_access);



        $access = AccessResult::allowedIf($has_caregiver_access->isAllowed())->andIf(AccessResult::allowedIfHasPermission($account, 'view ssr_student_leave_application'));
        return $access->addCacheableDependency($cache);

      case 'handle':
        return AccessResult::allowedIfHasPermissions($account, ['handle all ssr_student_leave_application', 'handle long ssr_student_leave_application'], 'OR');

      default:
        return AccessResult::neutral()->addCacheableDependency($cache);
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_student_leave_application', 'administer ssr_student_leave_application'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL): AccessResult {
    if ($operation === 'edit' && $field_definition->getName() === 'student' && !$items?->getEntity()->get('student')->isEmpty()) {
      return AccessResult::forbidden();
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
