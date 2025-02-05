<?php

namespace Drupal\simple_school_reports_schema_support\Service;

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

  public function clearLookup(): void;

  public function syncIsEnabled(): bool;

  public function getEnabledSettings(): array;

  public function setEnabledSettings(array $settings): void;

}
