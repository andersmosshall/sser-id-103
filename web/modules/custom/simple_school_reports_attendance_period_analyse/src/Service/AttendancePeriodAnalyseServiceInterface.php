<?php

namespace Drupal\simple_school_reports_attendance_period_analyse\Service;

use Drupal\simple_school_reports_entities\SchoolWeekInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining AttendancePeriodAnalyseService.
 */
interface AttendancePeriodAnalyseServiceInterface {

  /**
   * @return int[]
   */
  public function getAbsencePercentageLimits(): array;

  public function setAbsencePercentageLimits(array $limits): void;

  public function getAttendancePeriodData(array $uids, \DateTime $from, \DateTime $to): array;

}
