<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining UserMetaDataService.
 */
interface UserMetaDataServiceInterface {

  public const UNKNOWN_AGE = -99;

  /**
   * @param string $uid
   *
   * @return array
   */
  public function getMentorStudents(string $uid): array;

  /**
   * @param string $uid
   *
   * @return array
   */
  public function getCaregiverStudentsData(string $uid, bool $check_caregiver_access = FALSE): array;

  /**
   * @param string $uid
   *
   * @return array
   */
  public function getCaregiverStudents(string $uid, bool $check_caregiver_access = FALSE): array;

  /**
   * @param \Drupal\user\UserInterface $child
   * @param bool $only_caregivers_with_access
   *
   * @return string[]
   */
  public function getCaregiverUids(UserInterface $child, bool $only_caregivers_with_access = FALSE): array;

  /**
   * @param $child_uid
   * @param bool $only_caregivers_with_access
   *
   * @return \Drupal\user\UserInterface[]
   */
  public function getCaregivers(UserInterface $child, bool $only_caregivers_with_access = FALSE): array;

  /**
   * @param string $uid
   *
   * @return array
   */
  public function getTeacherCourses(string $uid): array;

  /**
   * @param array $uids
   *
   * @return array
   */
  public function getStudentCourses(array $uids): array;

  /**
   * @return array
   */
  public function getStudentGradesAgeData(bool $skip_ended = FALSE): array;

  /**
   * @param \Drupal\node\NodeInterface $budget
   *
   * @return array
   */
  public function getAgeGroupsFromBudgetNode(NodeInterface $budget): array;

  /**
   * @param bool $skip_ended
   *
   * @return int
   */
  public function getStudentCacheAgeMax(bool $skip_ended = FALSE): int;

  /**
   * @param bool $only_active
   *
   * @return array
   */
  public function getUserWeights(bool $only_active = TRUE): array;

  /**
   * @param string $uid
   * @param \DateTime|null $date
   *
   * @return int|null
   */
  public function getUserGrade(string $uid, ?\DateTime $date = NULL): ?int;

  /**
   * @param string $uid
   *
   * @return array
   */
  public function getUserSchoolGradeAndType(string $uid): array;

  /**
   * @param string $uid
   * @param \DateTime|null $date
   *
   * @return int
   */
  public function getUserRelativeGrade(?\DateTime $date = NULL): int;

  public function isAdult(string $uid): bool;

  public function getAdultUids(): array;

  public function caregiversHasAccess(string $uid): bool;

}
