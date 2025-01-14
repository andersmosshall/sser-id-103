<?php

namespace Drupal\simple_school_reports_attendance_analyse\Service;

use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining AttendanceAnalyseService.
 */
interface AttendanceAnalyseServiceInterface {

  /**
   * @param string $uid
   * @param \DateTime|null $date
   *
   * @return \Drupal\simple_school_reports_entities\SchoolWeekInterface|null
   */
  public function getSchoolWeek(string $uid, ?\DateTime $date = NULL): ?SchoolWeekInterface;

  /**
   * @param string $uid
   *   The user id to analyse.
   * @param \DateTime $from
   *   The start date of the period to analyse (will convert to 00:00:00).
   * @param \DateTime $to
   *   The end date of the period to analyse (will convert to 23:59:59).
   *
   * @return array
   *   An associative array containing:
   *    - total: The total school time in seconds.
   *    - attended: The total time attended in seconds.
   *    - reported_absence: The total time reported as absence in seconds.
   *    - leave_absence: The total time reported as leave of absence in seconds.
   *    - valid_absence: The total time reported as valid absence in seconds for attendance reports in courses.
   *    - invalid_absence: The total time reported as invalid absence in seconds for attendance reports in courses.
   *    - total_[1-7]: The total school time in seconds for each day of the week.
   *    - attended_[1-7]: The total time attended in seconds for each day of the week.
   *    - reported_absence_[1-7]: The total time reported as absence in seconds for each day of the week.
   *    - leave_absence_[1-7]: The total time reported as leave of absence in seconds for each day of the week.
   *    - valid_absence_[1-7]: The total time reported as valid absence in seconds for attendance reports in courses for each day of the week.
   *    - invalid_absence_[1-7]: The total time reported as invalid absence in seconds for attendance reports in courses for each day of the week.
   *    - adapted_studies: Boolean if user has adapted studies.
   *    - user_grade: The grade of the user for this period.
   *    - per_day: A list associative arrays containing the same keys as the other attendance statistics but for each day in the period. Each array is keyed by the date in the format 'Y-m-d'.
   *  NOTE: In all parameters only data that is in a valid school time is
   *  considered.
   */
  public function getAttendanceStatistics(string $uid, \DateTime $from, \DateTime $to, bool $include_future = FALSE): array;

  public function getAttendanceStatisticsAll(\DateTime $from, \DateTime $to): array;

  public function getAttendanceStatisticsViewsSortSource(\DateTime $from, \DateTime $to): array;

}
