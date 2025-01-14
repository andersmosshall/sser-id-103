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
 * Defines the access control handler for the examination entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ExaminationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    assert($entity instanceof ExaminationInterface);

    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
    }

    $common_cache = new CacheableMetadata();
    $common_cache->addCacheableDependency($entity);

    /** @var \Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface $assessment_group */
    $assessment_group = $entity->get('assessment_group')->entity;
    if (!$assessment_group) {
      return AccessResult::forbidden()->addCacheableDependency($common_cache);
    }

    $common_cache->addCacheableDependency($assessment_group);
    $common_cache->addCacheContexts(['user']);
    $is_main_teacher = $assessment_group->get('main_teacher')->target_id == $account->id();

    switch ($operation) {
      case 'view': {
        return AccessResult::allowedIfHasPermission($account, 'edit ssr_examination')
          ->orIf(AccessResult::allowedIf($assessment_group->hasAnyPermissions((string) $account->id(), [
            AssessmentGroupInterface::ALLOW_VIEW_EXAMINATION_ALL_RESULTS,
            AssessmentGroupInterface::ALLOW_EDIT_EXAMINATION_ALL_RESULTS,
          ])))
          ->addCacheableDependency($common_cache);
      }

      case 'update': {
        return AccessResult::allowedIfHasPermission($account, 'edit ssr_examination')
          ->orIf(AccessResult::allowedIf($is_main_teacher))
          ->orIf(AccessResult::allowedIf($assessment_group->access('add_examination', $account) && $entity->getOwnerId() == $account->id()))
          ->addCacheableDependency($common_cache);
      }

      case 'delete': {
        return AccessResult::allowedIfHasPermission($account, 'delete ssr_examination')
          ->orIf(AccessResult::allowedIf($is_main_teacher))
          ->orIf(AccessResult::allowedIf($assessment_group->access('add_examination', $account) && $entity->getOwnerId() == $account->id()))
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
    $current_request = \Drupal::request();
    $assessment_group_id = $current_request->query->get('assessment_group');
    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['route', 'url.query_args']);

    if (!$assessment_group_id) {
      $assessment_group_id = \Drupal::routeMatch()->getRawParameter('ssr_assessment_group');
    }

    if (!$assessment_group_id) {
      return AccessResult::forbidden()->addCacheableDependency($cache);
    }

    /** @var \Drupal\simple_school_reports_examinations_support\AssessmentGroupInterface|null $assessment_group */
    $assessment_group = \Drupal::entityTypeManager()->getStorage('ssr_assessment_group')->load($assessment_group_id);
    if (!$assessment_group) {
      return AccessResult::forbidden()->addCacheableDependency($cache);
    }

    return $assessment_group->access('add_examination', $account, TRUE);
  }

}
