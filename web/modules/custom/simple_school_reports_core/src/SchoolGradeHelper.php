<?php

namespace Drupal\simple_school_reports_core;

use Drupal\simple_school_reports_core\Service\SchoolGradeServiceInterface;

class SchoolGradeHelper {

  public const UNKNOWN_GRADE = SchoolGradeServiceInterface::UNKNOWN_GRADE;
  public const QUITED_GRADE = SchoolGradeServiceInterface::QUITED_GRADE;

  public static function getSupportedSchoolGrades(?array $school_type_filter = NULL, $check_module_enabled = TRUE): array {
    /** @var \Drupal\simple_school_reports_core\Service\SchoolGradeServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.school_grade');
    return $service->getSupportedSchoolGrades($school_type_filter, $check_module_enabled);
  }

  public static function getSchoolGradesMap(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    /** @var \Drupal\simple_school_reports_core\Service\SchoolGradeServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.school_grade');
    return $service->getActiveSchoolGradesMap($school_type_filter, $include_unknown, $include_quited);
  }

  public static function getSchoolGradesMapAll(): array {
    /** @var \Drupal\simple_school_reports_core\Service\SchoolGradeServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.school_grade');
    return $service->getActiveSchoolGradesMapAll();
  }

  public static function getSchoolGradesShortName(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    /** @var \Drupal\simple_school_reports_core\Service\SchoolGradeServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.school_grade');
    return $service->getActiveSchoolGradesShortName($school_type_filter, $include_unknown, $include_quited);
  }

  public static function getSchoolGradesLongName(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    /** @var \Drupal\simple_school_reports_core\Service\SchoolGradeServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.school_grade');
    return $service->getActiveSchoolGradesLongName($school_type_filter, $include_unknown, $include_quited);
  }

  public static function getSchoolGradeValues(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    /** @var \Drupal\simple_school_reports_core\Service\SchoolGradeServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.school_grade');

    return $service->getActiveSchoolGradeValues($school_type_filter, $include_unknown, $include_quited);
  }

  public static function getSchoolTypeByGrade(int $grade): ?string {
    /** @var \Drupal\simple_school_reports_core\Service\SchoolGradeServiceInterface $service */
    $service = \Drupal::service('simple_school_reports_core.school_grade');
    return $service->getSchoolTypeByGrade($grade);
  }

  public static function parseGradeValueToActualGrade(int $grade): int {
    return $grade % 100;
  }
}
