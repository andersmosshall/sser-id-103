<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_examinations_support;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an assessment group entity type.
 */
interface AssessmentGroupInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {
  const ALLOW_DELETE_ASSESSMENT_GROUP = 'delete_assessment_group';
  const ALLOW_ADMINISTER_ASSESSMENT_GROUP = 'administer_assessment_group';
  const ALLOW_ADD_EXAMINATIONS = 'add_examinations';
  const ALLOW_VIEW_EXAMINATION_ALL_RESULTS = 'view_examination_results';
  const ALLOW_EDIT_EXAMINATION_ALL_RESULTS = 'edit_examination_results';

  const ALL_PERMISSIONS = [
    self::ALLOW_DELETE_ASSESSMENT_GROUP,
    self::ALLOW_ADMINISTER_ASSESSMENT_GROUP,
    self::ALLOW_ADD_EXAMINATIONS,
    self::ALLOW_VIEW_EXAMINATION_ALL_RESULTS,
    self::ALLOW_EDIT_EXAMINATION_ALL_RESULTS,
  ];

  /**
   * @param string $uid
   *
   * @return string[]
   */
  public function getPermissions(string $uid): array;

  /**
   * @param string $uid
   * @param string $permission
   *
   * @return bool
   */
  public function hasPermission(string $uid, string $permission): bool;

  /**
   * @param string $uid
   * @param string[] $permissions
   *
   * @return bool
   */
  public function hasAnyPermissions(string $uid, array $permissions): bool;

}
