<?php

namespace Drupal\simple_school_reports_grade_registration\Service;

/**
 * Provides an interface defining AbsenceStatisticsService.
 */
interface GradeRoundProgressServiceInterface {

  /**
   * @param string $grade_round_nid
   *
   * @return string
   */
  public function getProgress(string $grade_round_nid) : string;

}
