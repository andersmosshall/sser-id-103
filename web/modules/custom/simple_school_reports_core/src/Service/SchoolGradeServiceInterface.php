<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_core\Form\ResetInvalidAbsenceMultipleForm;

/**
 * Interface for SchoolGradeService
 */
interface SchoolGradeServiceInterface {
  public const UNKNOWN_GRADE = -9999999;
  public const QUITED_GRADE = 9999999;

  public function getSupportedSchoolGrades(?array $school_type_filter = NULL, $check_module_enabled = TRUE): array;

  public function getActiveSchoolGradesMap(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE);

  public function getActiveSchoolGradesMapAll(): array;

  public function getActiveSchoolGradesShortName(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array;

  public function getActiveSchoolGradesLongName(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array;

  public function getActiveSchoolGradeValues(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array;

  public function getSchoolTypeByGrade(int $grade): ?string;



}
