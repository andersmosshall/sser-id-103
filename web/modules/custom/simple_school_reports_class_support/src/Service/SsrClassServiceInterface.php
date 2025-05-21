<?php

namespace Drupal\simple_school_reports_class_support\Service;

/**
 * Provides an interface defining SsrClassService.
 */
interface SsrClassServiceInterface {

  const STUDENT_SUFFIX_DEFAULT = 'default';
  const STUDENT_SUFFIX_CLASS = 'class';
  const STUDENT_SUFFIX_CLASS_GRADE = 'class_grade';

  /**
   * @param string $class_id
   *
   * @return array
   */
  public function getStudentIdsByClassId(string $class_id): array;

  /**
   * @param string $class_id
   *
   * @return array
   */
  public function getMentorIdsByClassId(string $class_id): array;

  /**
   * @param string $grade
   *
   * @return array
   */
  public function getClassIdsByGrade(string $grade): array;

  /**
   * @param string $student_id
   *
   * @return string|null
   */
  public function getStudentClassId(string $student_id): ?string;

  /**
   * @param bool $include_inactive
   *
   * @return \Drupal\simple_school_reports_class_support\SchoolClassInterface[]
   */
  public function getSortedClasses(bool $include_inactive = FALSE): array;

  public function getSortedClassOptions(bool $include_inactive = FALSE, $include_all_option = TRUE, bool $include_none_option = FALSE): array;

  /**
   * Not sorted.
   *
   * @param bool $include_inactive
   *
   * @return string[]
   */
  public function getClassIds(bool $include_inactive = FALSE): array;

  public function queueClassSync(string $class_id);

  public function syncClass(string $class_id);

}
