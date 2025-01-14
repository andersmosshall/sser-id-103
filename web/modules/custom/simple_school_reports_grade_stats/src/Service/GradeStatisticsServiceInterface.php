<?php

namespace Drupal\simple_school_reports_grade_stats\Service;

/**
 * Class AbsenceStatisticsService
 *
 * @package Drupal\simple_school_reports_grade_stats\Service
 */
interface GradeStatisticsServiceInterface {

  CONST NO_GRADE = 0;

  /**
   * @param string $grade_round_nid
   *
   * @return string
   */
  public function getGradeStatistics(string $grade_round_nid) : array;

  /***
   * @param string $grade_round_nid
   * @param int $student_uid
   *
   * @return array
   */
  public function getStudentGradeStatistics(string $grade_round_nid, int $student_uid) : array;

}
