<?php

namespace Drupal\simple_school_reports_schema_support\Service;

use Drupal\simple_school_reports_entities\CalendarEventInterface;

/**
 * Provides an interface defining CalendarEventsSyncService.
 */
interface CalendarEventsSyncServiceInterface {

  public const CALENDAR_EVENT_DAYS_AHEAD = 3;

  /**
   * Syncs calendar events with the school schema.
   */
  public function syncCourseCalendarEvents(string|int $course_id, int $from, int $to, bool $is_bulk_action): void;

  public function removeCalendarEventsForCourse(string|int $course_id, int $from, int $to, bool $is_bulk_action): void;

  /**
   * NOTE: This is not the stored calendar events, only unsaved calulated
   * events. Do not store.
   *
   * @param string|int $student_uid
   * @param int $from
   * @param int $to
   *
   * @return CalendarEventInterface[]
   */
  public function calculateStudentCourseCalendarEvents(string|int $student_uid, int $from, int $to): array;

  public function clearLookup(): void;

  public function syncIsEnabled(): bool;

  public function getEnabledSettings(): array;

  public function setEnabledSettings(array $settings): void;

}
