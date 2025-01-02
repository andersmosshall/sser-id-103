<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the examination result entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ExaminationResultAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    assert($entity instanceof ExaminationResultInterface);

    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
    }

    $common_cache = new CacheableMetadata();
    $common_cache->addCacheableDependency($entity);

    /** @var \Drupal\simple_school_reports_examinations_support\ExaminationInterface $examination */
    $examination = $entity->get('examination')->entity;
    if (!$examination) {
      return AccessResult::forbidden()->addCacheableDependency($common_cache);
    }
    $common_cache->addCacheableDependency($examination);

    /** @var \Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface $assessment_group */
    $assessment_group = $examination->get('assessment_group')->entity;
    if (!$assessment_group) {
      return AccessResult::forbidden()->addCacheableDependency($common_cache);
    }
    $common_cache->addCacheableDependency($assessment_group);

    /** @var \Drupal\user\UserInterface|null $student */
    $student = $entity->get('student')->entity;
    if (!$student) {
      return AccessResult::forbidden()->addCacheableDependency($common_cache);
    }

    switch ($operation) {
      case 'view': {
        $is_published = !!$examination->get('status')->value && !!$entity->get('status')->value;

        return AccessResult::allowedIfHasPermission($account, 'view ssr_examination_result')
          ->orIf(AccessResult::allowedIf($assessment_group->hasAnyPermissions((string) $account->id(), [
            AssessmentGroupInterface::ALLOW_VIEW_EXAMINATION_ALL_RESULTS,
            AssessmentGroupInterface::ALLOW_EDIT_EXAMINATION_ALL_RESULTS,
          ])))
          ->orIf($is_published
            ? $student->access('caregiver_access', $account, TRUE)
            : AccessResult::allowedIfHasPermission($account, 'school staff permission')
          )
          ->addCacheableDependency($common_cache);
      }

      case 'update': {
        return AccessResult::allowedIfHasPermission($account, 'edit ssr_examination_result')
          ->orIf(AccessResult::allowedIf($assessment_group->hasAnyPermissions((string) $account->id(), [
            AssessmentGroupInterface::ALLOW_EDIT_EXAMINATION_ALL_RESULTS,
          ])))
          ->addCacheableDependency($common_cache);
      }

      case 'delete': {
        return AccessResult::allowedIfHasPermission($account, 'delete ssr_examination_result')
          ->orIf(AccessResult::allowedIf($assessment_group->hasAnyPermissions((string) $account->id(), [
            AssessmentGroupInterface::ALLOW_EDIT_EXAMINATION_ALL_RESULTS,
          ])))
          ->addCacheableDependency($common_cache);
      }

      default: {
        return AccessResult::neutral();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_examination_result', 'administer ssr_examination_result'], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    $access = parent::checkFieldAccess($operation, $field_definition, $account, $items);

    if ($field_definition->getName() === 'status' && $examination = $items?->getEntity()?->get('examination')->entity) {
      if (!!$examination->get('status')->value) {
        return AccessResult::forbidden()->addCacheableDependency($examination);
      }
      $access->addCacheableDependency($examination);
    }

    return $access;
  }

}
