<?php

namespace Drupal\simple_school_reports_extension_proxy\Service;

/**
 * Class GradeSupportServiceInterface
 *
 * @package Drupal\simple_school_reports_grade_stats\Service
 */
interface GradeSupportServiceInterface {

  /**
   * @param string $grade_round_nid
   * @param string $subject_id
   * @param string $grade_system
   * @param array $student_ids
   *
   * @return array
   */
  public function getDefaultGradeRoundData(string $grade_round_nid, string $subject_id, string $grade_system, array $student_ids): array;

  /**
   * @param string|null $student_uid
   * @param \stdClass $grade_data
   *
   * @return string|null
   */
  public function resolveGender(?string $student_uid, \stdClass $grade_data): ?string;

}
