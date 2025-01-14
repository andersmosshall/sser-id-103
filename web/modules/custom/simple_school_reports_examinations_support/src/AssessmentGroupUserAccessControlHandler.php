<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the assessment group user entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class AssessmentGroupUserAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    assert($entity instanceof AssessmentGroupUserInterface);

    if ($entity->isNew()) {
      return AccessResult::allowedIfHasPermission($account, 'create ssr_assessment_group_user');
    }

    /**
     * @var \Drupal\simple_school_reports_examinations_support\Service\AssessmentGroupServiceInterface $assessment_group_service
     */
    $assessment_group_service = \Drupal::service('simple_school_reports_examinations_support.assessment_group_service');
    $assessment_group_id = $assessment_group_service->getAssessmentGroupIdFromGroupUserId((int) $entity->id());
    $assessment_group = $assessment_group_id ? \Drupal::entityTypeManager()->getStorage('ssr_assessment_group')->load($assessment_group_id) : NULL;
    if (!$assessment_group) {
      return AccessResult::allowedIfHasPermission($account, 'create ssr_assessment_group_user')->addCacheableDependency($entity);
    }

    return $assessment_group->access('update', $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create ssr_assessment_group', 'administer ssr_assessment_group'], 'OR');
  }

}
