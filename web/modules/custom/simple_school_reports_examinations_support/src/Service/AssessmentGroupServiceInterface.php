<?php

namespace Drupal\simple_school_reports_examinations_support\Service;

/**
 * Provides an interface defining SsrAssessmentGroupService.
 */
interface AssessmentGroupServiceInterface {

  /**
   * @param int $group_user_id
   *
   * @return int|null
   */
  public function getAssessmentGroupIdFromGroupUserId(int $group_user_id): ?int;

  /**
   * @param int $teacher_uid
   *
   * @return int[]
   */
  public function getRelatedAssessmentGroupsByTeacher(int $teacher_uid): array;

}
