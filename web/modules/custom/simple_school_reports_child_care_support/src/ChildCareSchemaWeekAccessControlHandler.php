<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_child_care_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the child care schema week entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ChildCareSchemaWeekAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    /** @var \Drupal\simple_school_reports_child_care_support\ChildCareSchemaWeekInterface $entity */
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    if ($entity->isNew() || $operation === 'create') {
      return $this->checkCreateAccess($account, [], $entity->bundle());
    }

    $child_care_schema = $entity->getParentSchema();
    if (!$child_care_schema) {
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    if ($operation === 'delete') {
      $operation = 'update';
    }
    return $child_care_schema->access($operation, $account, TRUE)->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    $cache = new CacheableMetadata();
    $cache->setCacheContexts(['route']);

    $student = \Drupal::routeMatch()->getParameter('user');
    $child_care = \Drupal::routeMatch()->getParameter('ssr_child_care');

    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $parent */
    $parent = $student ?? $child_care;

    if (!$parent) {
      return AccessResult::forbidden()->addCacheableDependency($cache);
    }

    $cache->addCacheableDependency($parent);
    return $parent->access('update', $account, TRUE)->addCacheableDependency($cache);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $field_name = $field_definition->getName();
    // Check if last character is a number.
    $day_index = substr($field_name, -1);
    if (is_numeric($day_index)) {
      // Prevent access to sat and sun fields for now.
      if ($day_index > 5) {
        return AccessResult::forbidden();
      }
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
