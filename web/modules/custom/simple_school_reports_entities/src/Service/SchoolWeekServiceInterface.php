<?php

namespace Drupal\simple_school_reports_entities\Service;

use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining SchoolWeekService.
 */
interface SchoolWeekServiceInterface {

  const DEVIATION_COMMENT_GRADE = 1;
  const DEVIATION_COMMENT_ADAPTED_STUDIES = 2;
  const DEVIATION_COMMENT_SCHOOL_WEEK = 3;
  const DEVIATION_COMMENT_SCHOOL_CLASS = 4;

  /**
   * @param string $uid
   * @param \DateTime|null $date
   *
   * @return \Drupal\simple_school_reports_entities\SchoolWeekInterface|null
   */
  public function getSchoolWeek(string $uid, ?\DateTime $date = NULL, bool $only_root_school_weeks = FALSE): ?SchoolWeekInterface;

  /**
   * Get school weeks relevant for a group of users.
   *
   * @param string[] $uids
   *   User ids.
   *
   * @return SchoolWeekInterface[]
   */
  public function getSchoolWeeksRelevantForUsers(array $uids, ?\DateTime $date = NULL, bool $only_root_school_weeks = TRUE): array;

  /**
   * @param string $school_week_id
   *
   * @return \Drupal\simple_school_reports_entities\SchoolWeekInterface|null
   */
  public function getStudentIdsRelevantForSchoolWeek(string $school_week_id): array;

  /**
   * @param string $class_id
   *
   * @return \Drupal\simple_school_reports_entities\SchoolWeekInterface|null
   */
  public function getSchoolWeekByClassId(string $class_id): ?SchoolWeekInterface;

  /**
   * @param string $school_week_id
   *
   * @return array
   *   Associative array with keys 'type' and 'id'.
   */
  public function getSchoolWeekReference(string $school_week_id): array;

  /**
   * @return array
   */
  public function getDeviationIdsInUse(): array;

  /**
   * @param \Drupal\simple_school_reports_entities\SchoolWeekInterface $school_week
   *
   * @return array
   */
  public function getSchoolWeekDeviationIds(SchoolWeekInterface $school_week): array;

  /**
   * @param \Drupal\simple_school_reports_entities\SchoolWeekInterface $school_week
   *
   * @return array
   */
  public function getSchoolWeekDeviationMap(SchoolWeekInterface $school_week): array;

  /**
   * @param string $deviation_id
   *
   * @return array|null
   */
  public function getDeviationData(string $deviation_id): ?array;

  /**
   * @return string
   */
  public function getDeviationViewsDisplay(): string;

}
