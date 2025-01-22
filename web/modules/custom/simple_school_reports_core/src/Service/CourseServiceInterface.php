<?php

namespace Drupal\simple_school_reports_core\Service;

/**
 * Provides an interface defining CourseService.
 */
interface CourseServiceInterface {

  public function getStudentIdsInCourse(int|string $course_id, string $sub_group = 'default'): array;

  public function getCourseName(int|string $course_id, string $sub_group = 'default'): string;

  public function getSubGroupName(int|string $course_id, string $sub_group): string;

}
