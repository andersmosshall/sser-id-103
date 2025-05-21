<?php

namespace Drupal\simple_school_reports_core\Service;

/**
 * Provides an interface defining AbsenceStatisticsService.
 */
interface AbsenceStatisticsServiceInterface {

  /**
   * @param int $from
   * @param int $to
   *
   * @return array
   */
  public function getAllInvalidAbsenceData(int $from, int $to) : array;

  /**
   * @param int $from
   * @param int $to
   *
   * @return array
   */
  public function getAllAbsenceDayData(int $from, int $to) : array;

  public function getUserDayAbsenceItems(string $date_string, int $uid): array;

}
