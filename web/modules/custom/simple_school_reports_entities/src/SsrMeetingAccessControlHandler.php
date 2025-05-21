<?php

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the meeting entity type.
 */
class SsrMeetingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view meeting');

      case 'update':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['edit meeting', 'administer meeting'],
          'OR',
        );

      case 'delete':
        return AccessResult::allowedIfHasPermissions(
          $account,
          ['delete meeting', 'administer meeting'],
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
      ['create meeting', 'administer meeting'],
      'OR',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL, $return_as_object = FALSE) {
    if ($field_definition->getName() === 'meta') {
      if (!$account || !$account->hasPermission('administer meeting')) {
        $access = AccessResult::forbidden()->cachePerPermissions();
        return $return_as_object ? $access : $access->isAllowed();
      }
    }

    return parent::fieldAccess($operation, $field_definition, $account, $items, $return_as_object);
  }

}
