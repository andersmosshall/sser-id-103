<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the child care schema entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ChildCareSchemaAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    /** @var \Drupal\simple_school_reports_child_care_support\ChildCareSchemaInterface $entity */
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($account->hasPermission('administer simple school reports settings')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $cache_tags = [];
    $student_id = $entity->get('student')->target_id;
    if ( $student_id) {
      $cache_tags[] = 'ssr_child_care_schema_list:student:' . $student_id;
    }
    $child_care_id = $entity->get('child_care')->target_id;
    if ( $child_care_id) {
      $cache_tags[] = 'ssr_child_care_schema_list:child_care:' . $child_care_id;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $parent = NULL;

    if (!$entity->get('student')->isEmpty()) {
      /** @var \Drupal\user\UserInterface|null $student */
      $student = $entity->get('student')->entity;
      if ($student) {
        $parent = $student;
      }
    }

    if (!$entity->get('child_care')->isEmpty()) {
      /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface|null $child_care */
      $child_care = $entity->get('child_care')->entity;
      if ($child_care) {
        $parent = $child_care;
      }
    }


    if ($operation === 'view') {
      $access = AccessResult::allowedIfHasPermission($account, 'view ssr_child_care_schema');
      $access->cachePerUser();

      if (!$entity->get('student')->isEmpty()) {
        /** @var \Drupal\user\UserInterface|null $student */
        $student = $entity->get('student')->entity;
        if (!$student) {
          $access->andIf(AccessResult::forbidden());
        }
        else {
          $access->andIf($student->access('caregiver_access', $account, TRUE));
          $parent = $student;
        }
      }

      if (!$entity->get('child_care')->isEmpty()) {
        /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface|null $child_care */
        $child_care = $entity->get('child_care')->entity;
        if (!$child_care) {
          $access->andIf(AccessResult::forbidden());
        }
        else {
          $access->andIf($child_care->access('view', $account, TRUE));
          $parent = $child_care;
        }
      }

      return $access->addCacheableDependency($entity)->addCacheTags($cache_tags);
    }

    if (!$entity->isEditable() || !$parent) {
      return AccessResult::forbidden()->addCacheableDependency($entity)->addCacheTags($cache_tags);
    }

    return match($operation) {
      'update' => AccessResult::allowedIfHasPermission($account, 'edit ssr_child_care_schema')
        ->orIf($parent->access('update', $account, TRUE))
        ->addCacheableDependency($entity)
        ->addCacheTags($cache_tags),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete ssr_child_care_schema')
        ->orIf($parent->access('update', $account, TRUE))
        ->addCacheableDependency($entity)
        ->addCacheTags($cache_tags),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_child_care_schema', 'administer ssr_child_care_schema'], 'OR');
  }

  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {

    $entity = $items?->getEntity();


    if ($operation === 'edit' && $field_definition->getName() === 'student' && !$items?->getEntity()->get('student')->isEmpty()) {
      return AccessResult::forbidden();
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  protected function isActive($entity) {
    return $entity->get('student')->isEmpty();
  }

}
