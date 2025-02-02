<?php

namespace Drupal\simple_school_reports_schema_support\Service;

use Drupal\simple_school_reports_entities\CalendarEventInterface;

/**
 * Provides an interface defining SchemaSupportService.
 */
interface SchemaSupportServiceInterface {

  /**
   * @param \Drupal\simple_school_reports_entities\CalendarEventInterface $calendar_event
   * @param bool $include_course_name
   *
   * @return string
   */
  public function resolveCalenderEventName(CalendarEventInterface $calendar_event, bool $include_course_name = TRUE): string;

  /**
   * @param \Drupal\simple_school_reports_entities\CalendarEventInterface $calendar_event
   *
   * @return string[]
   */
  public function getStudentIds(CalendarEventInterface $calendar_event): array;

  /**
   * @param string $student_id
   */
  public function getStudentSchemaId(string $student_id): ?string;

}
