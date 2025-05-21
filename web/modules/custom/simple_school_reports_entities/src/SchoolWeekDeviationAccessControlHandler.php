<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the school week deviation entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class SchoolWeekDeviationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view school_week_deviation'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit school_week_deviation'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete school_week_deviation'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create school_week_deviation', 'administer school_week_deviation'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $result = parent::checkFieldAccess($operation, $field_definition, $account, $items);
    if ($result->isAllowed()) {
      if ($field_definition->getName() === 'grade' && $operation === 'edit') {
        $entity = $items->getEntity();
        if ($entity?->id()) {
          /** @var \Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface $school_week_service */
          $school_week_service = \Drupal::service('simple_school_reports_entities.school_week_service');
          $dev_data = $school_week_service->getDeviationData($entity->id()) ?? [];
          if (!empty($dev_data)) {
            $first_item = reset($dev_data);
            if ($first_item['reference'] === 'specific') {
              return AccessResult::forbidden()->addCacheableDependency($result);
            }
          }
        }
      }

    }
    return $result;
  }

}
