<?php

namespace Drupal\simple_school_reports_core;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;
use Drupal\Core\Site\Settings;

class SchoolGradeHelper {

  public const UNKNOWN_GRADE = -9999999;
  public const QUITED_GRADE = 9999999;


  public static function hasMultipleSchoolTypes(): bool {
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');
    $types = 0;
    $use_gr = $module_handler->moduleExists('simple_school_reports_core_gr');
    $use_gy = $module_handler->moduleExists('simple_school_reports_core_gy');

    if ($use_gr) {
      $types++;
    }
    if ($use_gy) {
      $types++;
    }

    return $types > 1;
  }

  public static function getSchoolGradesMap(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {

    $school_type_filter = $school_type_filter === NULL
      ? ['FKLASS', 'GR', 'GY']
      : $school_type_filter;

    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = \Drupal::service('module_handler');

    $use_fklass = in_array('FKLASS', $school_type_filter) && $module_handler->moduleExists('simple_school_reports_core_gr');
    $use_gr = in_array('GR', $school_type_filter) && $module_handler->moduleExists('simple_school_reports_core_gr');
    $use_gy = in_array('GY', $school_type_filter) && $module_handler->moduleExists('simple_school_reports_core_gy');

    $return = [];

    if ($include_unknown) {
      $return[self::UNKNOWN_GRADE] = t('Unknown grade');
    }

    $grade_from = Settings::get('ssr_grade_from', 0);
    $grade_to = Settings::get('ssr_grade_to', 9);

    if ($use_fklass && is_numeric($grade_from) && is_numeric($grade_to) && $grade_to >= $grade_from) {
      for ($i = $grade_from; $i <= $grade_to; $i++) {
        if ($i === 0) {
          $return[0] = t('Pre school class');
          break;
        }
      }
    }

    if ($use_gr && is_numeric($grade_from) && is_numeric($grade_to) && $grade_to >= $grade_from) {
      $grade_adjust = 0;
      for ($i = $grade_from; $i <= $grade_to; $i++) {
        if ($i < 1) {
          continue;
        }

        $grade_value = $grade_adjust + $i;
        $return[$grade_value] = 'Åk ' . $i;
      }
    }

    $grade_from = Settings::get('ssr_gy_grade_from', 1);
    $grade_to = Settings::get('ssr_gy_grade_to', 3);
    if ($use_gy && is_numeric($grade_from) && is_numeric($grade_to) && $grade_to >= $grade_from) {
      $grade_adjust = 10000;
      for ($i = $grade_from; $i <= $grade_to; $i++) {
        if ($i < 1) {
          continue;
        }

        $grade_value = $grade_adjust + $i;
        $return[$grade_value] = 'Gy ' . $i;
      }
    }


    if ($include_quited) {
      $return[self::QUITED_GRADE] = t('Student has quit');
    }
    return $return;
  }

  public static function getSchoolGradesMapAll(): array {
    return self::getSchoolGradesMap(NULL, TRUE, TRUE);
  }

  public static function getSchoolGradesShortName(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    $return = self::getSchoolGradesMap($school_type_filter, $include_unknown, $include_quited);
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

  public static function getSchoolGradesLongName(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    $return = self::getSchoolGradesMap($school_type_filter, $include_unknown, $include_quited);

    $use_suffix = is_array($school_type_filter) && $school_type_filter <= 1
      ? FALSE
      : self::hasMultipleSchoolTypes();

    foreach ($return as $grade => $label) {
      if ($grade === self::UNKNOWN_GRADE || $grade === self::QUITED_GRADE) {
        continue;
      }
      if ($grade < 0) {
        continue;
      }

      $grade_value = self::parseGradeValueToActualGrade($grade);
      $label = t('Grade @grade', ['@grade' => $grade_value]);

      if ($use_suffix) {
        $school_type = self::getSchoolTypeByGrade($grade);
        $label .= ' (' . $school_type . ')';
      }

      if ($grade_value === 0) {
        $label = t('Pre school class');
      }

      $return[$grade] = $label;
    }

    return $return;
  }

  public static function getSchoolGradeValues(?array $school_type_filter = NULL, bool $include_unknown = FALSE, bool $include_quited = FALSE): array {
    return array_keys(self::getSchoolGradesMap($school_type_filter, $include_unknown, $include_quited));
  }

  public static function getSchoolTypeByGrade(int $grade): ?string {
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

  public static function parseGradeValueToActualGrade(int $grade): int {
    return $grade % 100;
  }
}
