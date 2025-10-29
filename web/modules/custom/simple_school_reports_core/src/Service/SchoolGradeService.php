<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_core\SchoolGradeHelper;

/**
 * Class SchoolGradeService
 */
class SchoolGradeService implements SchoolGradeServiceInterface {

  use StringTranslationTrait;

  protected array $lookup = [];

  /**
   * SchoolSubjectService constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $connection,
    protected CacheBackendInterface $cache,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  protected function hasMultipleSchoolTypes(): bool {
    $types = 0;

    $types_to_check = ['fklass', 'gr', 'gy'];

    foreach ($types_to_check as $type) {
      if ($this->moduleHandler->moduleExists('simple_school_reports_core_' . $type)) {
        $types++;
      }
    }

    return $types > 1;
  }

  protected function getActivatedSchoolGrades(): array {
    $cid = 'activated_school_grades';
    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $school_grades = [];

    $results = $this->connection->select('ssr_organization__school_grades', 'sg')
      ->fields('sg', ['school_grades_value'])
      ->execute();

    foreach ($results as $result) {
      $school_grade_value = $result->school_grades_value;
      $school_grades[$school_grade_value] = $school_grade_value;
    }

    $school_grades = array_values($school_grades);
    $this->lookup[$cid] = $school_grades;
    return $school_grades;
  }

  public function getSupportedSchoolGrades(?array $school_type_filter = NULL, $check_module_enabled = TRUE): array {
    $school_type_filter = $school_type_filter === NULL
      ? ['FKLASS', 'GR', 'GY']
      : $school_type_filter;

    $use_fklass = in_array('FKLASS', $school_type_filter);
    $use_gr = in_array('GR', $school_type_filter);
    $use_gy = in_array('GY', $school_type_filter);

    if ($check_module_enabled) {
      $use_fklass = in_array('FKLASS', $school_type_filter) && $this->moduleHandler->moduleExists('simple_school_reports_core_gr');
      $use_gr = in_array('GR', $school_type_filter) && $this->moduleHandler->moduleExists('simple_school_reports_core_gr');
      $use_gy = in_array('GY', $school_type_filter) && $this->moduleHandler->moduleExists('simple_school_reports_core_gy');
    }

    $return = [];
    if ($use_fklass) {
      $return[0] = $this->t('Pre school class');
    }

    if ($use_gr) {
      $grade_from = 1;
      $grade_to = 9;
      $grade_adjust = 0;
      for ($i = $grade_from; $i <= $grade_to; $i++) {
        $grade_value = $grade_adjust + $i;
        $return[$grade_value] = 'Åk ' . $i;
      }
    }

    if ($use_gy) {
      $grade_from = 1;
      $grade_to = 5;
      $grade_adjust = 10000;
      for ($i = $grade_from; $i <= $grade_to; $i++) {
        $grade_value = $grade_adjust + $i;
        $return[$grade_value] = 'Gy ' . $i;
      }
    }

    return $return;
  }

  public function getActiveSchoolGradesMap(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    $return = [];

    if ($include_unknown) {
      $return[self::UNKNOWN_GRADE] = $this->t('Unknown grade');
    }

    $supported_grades = $this->getSupportedSchoolGrades($school_type_filter);
    $activated_grades = $this->getActivatedSchoolGrades();
    foreach ($supported_grades as $grade => $grade_label) {
      if (in_array($grade, $activated_grades)) {
        $return[$grade] = $grade_label;
      }
    }

    if ($include_quited) {
      $return[self::QUITED_GRADE] = $this->t('Student has quit');
    }
    return $return;
  }

  public function getActiveSchoolGradesMapAll(): array {
    return $this->getActiveSchoolGradesMap(NULL, TRUE, TRUE);
  }

  public function getActiveSchoolGradesShortName(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    $return = $this->getActiveSchoolGradesMap($school_type_filter, $include_unknown, $include_quited);
    if (isset($return[self::UNKNOWN_GRADE])) {
      $return[self::UNKNOWN_GRADE] = 'O';
    }
    if (isset($return[0])) {
      $return[0] = 'Åk F';
    }
    if (isset($return[self::QUITED_GRADE])) {
      $return[self::QUITED_GRADE] = 'S';
    }
    return $return;
  }

  public function getActiveSchoolGradesLongName(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    $return = $this->getActiveSchoolGradesMap($school_type_filter, $include_unknown, $include_quited);

    $use_suffix = !(is_array($school_type_filter) && $school_type_filter <= 1) && $this->hasMultipleSchoolTypes();

    foreach ($return as $grade => $label) {
      if ($grade === self::UNKNOWN_GRADE || $grade === self::QUITED_GRADE) {
        continue;
      }
      if ($grade < 0) {
        continue;
      }

      $grade_value = SchoolGradeHelper::parseGradeValueToActualGrade($grade);
      $label = $this->t('Grade @grade', ['@grade' => $grade_value]);

      if ($use_suffix) {
        $school_type = $this->getSchoolTypeByGrade($grade);
        $label .= ' (' . $school_type . ')';
      }

      if ($grade_value === 0) {
        $label = $this->t('Pre school class');
      }

      $return[$grade] = $label;
    }

    return $return;
  }

  public function getActiveSchoolGradeValues(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    return array_keys($this->getActiveSchoolGradesMap($school_type_filter, $include_unknown, $include_quited));
  }

  public function getSchoolTypeByGrade(int $grade): ?string {
    if ($grade === self::UNKNOWN_GRADE) {
      return NULL;
    }
    if ($grade === self::QUITED_GRADE) {
      return NULL;
    }

    if ($grade < 0) {
      return 'FS';
    }
    if ($grade === 0) {
      return 'FKLASS';
    }
    if ($grade >= 1 && $grade <= 100) {
      return 'GR';
    }
    if ($grade > 10000 && $grade < 10100) {
      return 'GY';
    }

    return 'AU';
  }

}
