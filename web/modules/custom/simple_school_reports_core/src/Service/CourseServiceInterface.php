<?php

namespace Drupal\simple_school_reports_core\Service;

/**
 * Provides an interface defining CourseService.
 */
interface CourseServiceInterface {

  public function getStudentIdsInCourse(int|string $course_id, string $sub_group = 'default'): array;

  public function getActiveCourseIdsWithStudents(): array;

  public function getCourseName(int|string $course_id, string $sub_group = 'default'): string;

  public function getSubGroupName(int|string $course_id, string $sub_group): string;

  /**
   * Get schema entry data for a course.
   *
   * NOTE: Courses with no active students will result in empty list.
   *
   * @param string|int $course_id
   *   The course id.
   * @param bool $warm_up_cache
   *   (Optional) If cache should be warmed up, e.g. if expected more calls for
   *   multiple courses are expected.
   *
   * @return array[]
   *   A list of schema entry data as arrays with the following keys:
   *    - source
   *    - from
   *    - to
   *    - week_day
   *    - periodicity
   *    - sub_group_id
   *    - subject
   *    - periodicity_week
   *    - periodicity_start_week
   */
  public function getSchemaEntryData(string|int $course_id, bool $warm_up_cache = FALSE): array;

  /**
   * Get schema entry data for a student.
   *
   * @param string|int $student_id
   *   The student user id.
   * @return array[]
   *   A list of schema entry data as arrays with the following keys:
   *    - source
   *    - from
   *    - to
   *    - week_day
   *    - periodicity
   *    - sub_group_id
   *    - subject
   *    - periodicity_week
   *    - periodicity_start_week
   */
  public function getStudentSchemaEntryData(string|int $student_id): array;

  public function getStudentSchemaEntryDataIdentifiers(string|int $student_id): array;

  public function getStudentSchemaEntryDataIdentifiersHash(string|int $student_id): ?string;

  public function getStudentSchemaEntryDataByHash(string $hash): array;

  public function getAllSchemaEntryDataIdentifiersHashes(): array;

  public function clearLookup(): void;

}
