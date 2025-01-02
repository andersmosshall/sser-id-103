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
  public function getSchoolWeek(string $uid, ?\DateTime $date = NULL): ?SchoolWeekInterface;

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
