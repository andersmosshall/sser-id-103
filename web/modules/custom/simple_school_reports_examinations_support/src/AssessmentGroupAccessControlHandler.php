<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the assessment group entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class AssessmentGroupAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    assert($entity instanceof AssessmentGroupInterface);

    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
    }

    $common_cache = new CacheableMetadata();
    $common_cache->addCacheableDependency($entity);
    $common_cache->addCacheContexts(['user']);
    $common_cache->addCacheTags(['ssr_assessment_group_user_list:' . $entity->id()]);

    switch ($operation) {
      case 'view': {
        return AccessResult::allowedIfHasPermission($account, 'view ssr_assessment_group');
      }

      case 'update': {
        return AccessResult::allowedIfHasPermission($account, 'edit ssr_assessment_group')
          ->orIf(AccessResult::allowedIf($entity->hasPermission((string) $account->id(), AssessmentGroupInterface::ALLOW_ADMINISTER_ASSESSMENT_GROUP)))
          ->addCacheableDependency($common_cache);
      }

      case 'delete': {
        return AccessResult::allowedIfHasPermission($account, 'delete ssr_assessment_group')
          ->orIf(AccessResult::allowedIf($entity->hasPermission((string) $account->id(), AssessmentGroupInterface::ALLOW_DELETE_ASSESSMENT_GROUP)))
          ->addCacheableDependency($common_cache);
      }

      case 'main_teacher_actions': {
        $is_main_teacher = $entity->get('main_teacher')->target_id == $account->id();

        return AccessResult::allowedIfHasPermissions($account, ['edit ssr_assessment_group', 'delete ssr_assessment_group'])
          ->orIf(AccessResult::allowedIf($is_main_teacher))
          ->addCacheableDependency($common_cache);
      }

      case 'add_examination': {
        return AccessResult::allowedIfHasPermission($account, 'delete ssr_assessment_group')
          ->orIf(AccessResult::allowedIf($entity->hasPermission((string) $account->id(), AssessmentGroupInterface::ALLOW_ADD_EXAMINATIONS)))
          ->addCacheableDependency($common_cache);
      }

      case 'view_all_results': {
        return AccessResult::allowedIfHasPermission($account, 'delete ssr_assessment_group')
          ->orIf(AccessResult::allowedIf($entity->hasPermission((string) $account->id(), AssessmentGroupInterface::ALLOW_VIEW_EXAMINATION_ALL_RESULTS)))
          ->addCacheableDependency($common_cache);
      }

      case 'handle_all_results': {
        $internal_permissions = [
          AssessmentGroupInterface::ALLOW_EDIT_EXAMINATION_ALL_RESULTS,
        ];
        return AccessResult::allowedIfHasPermission($account, 'delete ssr_assessment_group')
          ->orIf(AccessResult::allowedIf($entity->hasAnyPermissions((string) $account->id(), $internal_permissions)))
          ->addCacheableDependency($common_cache);
      }

      default:
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_assessment_group', 'administer ssr_assessment_group'], 'OR');
  }

}
