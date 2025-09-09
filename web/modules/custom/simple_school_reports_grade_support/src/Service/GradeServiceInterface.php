<?php

namespace Drupal\simple_school_reports_grade_support\Service;

use Drupal\simple_school_reports_grade_support\GradeInterface;

/**
 * Provides an interface defining GradeService.
 */
interface GradeServiceInterface {

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
   *     [$student_id][$syllabus_id][$grade_version_id] = \Drupal\simple_school_reports_grade_support\Utilities\GradeInfo
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
   * @param int $tid
   *
   * @return string|null
   */
  public function getGradeLabel(int $tid): ?string;



}
