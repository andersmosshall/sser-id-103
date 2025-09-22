<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\simple_school_reports_grade_support\GradeInterface;
use Drupal\simple_school_reports_grade_support\Utilities\GradeInfo;

/**
 * Provides an interface defining GradeService.
 */
interface GradeServiceInterface {

  /**
   * @param array|null $syllabus_ids
   * @param $only_active
   *
   * @return array
   */
  public function getStudentIdsWithGrades(?array $syllabus_ids, $only_active = TRUE): array;

  /**
   * @param array $student_ids
   * @param array|null $syllabus_ids
   *
   * @return \Drupal\simple_school_reports_grade_support\Utilities\GradeReference[]
   *   Keyed by revision id.
   */
  public function getGradeReferences(array $student_ids, ?array $syllabus_ids = NULL): array;

  /**
   * Parsed grades.
   *
   * Student ids and syllabus ids keys are sorted.
   *
   * @param array $grade_references
   *
   * @return array
   *   Array with structure:
   *     [$student_id][$syllabus_id] = \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo
   */
  public function parseGradesFromReferences(array $grade_references): array;

  /**
   * @param array $student_ids
   * @param array|null $syllabus_ids
   *
   * @return array
   *   @see return of parseGradesFromReferences()
   */
  public function parseGradesFromFilter(array $student_ids, ?array $syllabus_ids = NULL): array;

  /**
   * @param \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $grade_info
   *
   * @return string|null
   */
  public function getGradeLabel(GradeInfo $grade_info, ?array $exclude_label_map = []): ?string;

  /**
   * @param string|int $tid
   *
   * @return string|null
   */
  public function getGradeLabelFromTermId(string|int $tid): ?string;

  /**
   * @param \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $grade_info
   *
   * @return string
   */
  public function getCourseCode(GradeInfo $grade_info): string;

  /**
   * @param \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo $grade_info
   *
   * @return string
   */
  public function getSyllabusLabel(GradeInfo $grade_info): string;



}
