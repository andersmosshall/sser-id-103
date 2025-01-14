<?php

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the school week entity type.
 */
class SchoolWeekAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view school week');

      case 'update':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['edit school week', 'administer school week'],
          'OR',
        );

      case 'delete':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['delete school week', 'administer school week'],
          'OR',
        );

      default:
        // No opinion.
        return AccessResult::neutral();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions(
      $account,
      ['create school week', 'administer school week'],
      'OR',
    );
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
