<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\node\NodeInterface;

/**
 * Provides an interface defining UserMetaDataService.
 */
interface UserMetaDataServiceInterface {

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
  public function getCaregiverStudentsData(string $uid): array;

  /**
   * @param string $uid
   *
   * @return array
   */
  public function getCaregiverStudents(string $uid): array;

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
   * @param \DateTime|null $date
   *
   * @return int
   */
  public function getUserRelativeGrade(?\DateTime $date = NULL): int;

}
