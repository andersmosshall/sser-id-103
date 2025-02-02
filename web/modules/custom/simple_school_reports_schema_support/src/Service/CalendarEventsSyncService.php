<?php

namespace Drupal\simple_school_reports_schema_support\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_school_reports_core\Service\CourseServiceInterface;
use Drupal\simple_school_reports_core\Service\TermService;
use Drupal\simple_school_reports_entities\Service\SchoolWeekServiceInterface;
use Drupal\simple_school_reports_schema_support\CourseCalendarEventTrait;
use Drupal\simple_school_reports_schema_support\Events\MakeCourseCalendarEvent;
use Drupal\simple_school_reports_schema_support\Events\SsrSchemaSupportEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 */
class CalendarEventsSyncService implements CalendarEventsSyncServiceInterface {

  use CourseCalendarEventTrait;

  protected array $lookup = [];

  public function __construct(
    protected CourseServiceInterface $courseService,
    protected TermService $termService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $connection,
    protected EventDispatcherInterface $dispatcher,
    protected SchoolWeekServiceInterface $schoolWeekService,
  ) {}

  protected function warmUpStoredCalendarEventsCache(string|int $course_id, int $from, int $to, bool $warm_up_all): string {
    $cid = 'stored_calendar_events:' . $from . ':' . $to;
    if ($warm_up_all) {
      $cid .= ':all';
    }
    else {
      $cid .= ':' . $course_id;
    }
    if (array_key_exists($cid, $this->lookup)) {
      if (empty($this->lookup[$cid][$course_id])) {
        $this->lookup[$cid][$course_id] = [];
      }
      return $cid;
    }

    $this->lookup[$cid] = [];
    if (empty($this->lookup[$cid][$course_id])) {
      $this->lookup[$cid][$course_id] = [];
    }



    $courses = $warm_up_all ? $this->courseService->getActiveCourseIdsWithStudents() : [$course_id];
    if (!empty($courses)) {
      $query = $this->connection->select('ssr_calendar_event', 'ce');
      $query->innerJoin('ssr_calendar_event__field_course', 'fc', 'ce.id = fc.entity_id');
      $query->condition('fc.field_course_target_id', $courses, 'IN');
      $query->condition('ce.from', $from, '>=');
      $query->condition('ce.from', $to, '<');
      $results = $query->fields('fc', ['field_course_target_id'])
        ->fields('ce', ['id', 'identifier', 'completed', 'cancelled'])
        ->execute();

      foreach ($results as $result) {
        $this->lookup[$cid][$result->field_course_target_id][$result->identifier ?? $result->id] = [
          'id' => $result->id,
          'completed' => $result->completed,
          'cancelled' => $result->cancelled,
        ];
      }
    }

    return $cid;
  }

  public function syncCourseCalendarEvents(string|int $course_id, int $from, int $to, bool $is_bulk_action): void {
    if (!ssr_use_schema()) {
      return;
    }
    /** @var \Drupal\node\NodeInterface $course */
    $course = $this->entityTypeManager->getStorage('node')->load($course_id);
    if (!$course || $course->getType() !== 'course') {
      return;
    }

    $sync_from = $from;
    $sync_to = $to;

    if ($sync_to < $sync_from) {
      $tmp = $sync_to;
      $sync_to = $sync_from;
      $sync_from = $tmp;
    }

    $current_term_start = $this->termService->getCurrentTermStart(FALSE);
    $current_term_end = $this->termService->getCurrentTermEnd(FALSE);

    $do_dispatch = TRUE;
    if (!$current_term_start || !$current_term_end) {
      $do_dispatch = FALSE;
    }
    elseif ($sync_to <= $current_term_start || $sync_from >= $current_term_end) {
      $do_dispatch = FALSE;
    }
    else {
      $sync_to = min($sync_to, $current_term_end);
      $sync_from = max($sync_from, $current_term_start);
    }

    $event = new MakeCourseCalendarEvent($course_id, $sync_from, $sync_to, $is_bulk_action);
    if ($do_dispatch) {
      $this->dispatcher->dispatch($event, SsrSchemaSupportEvents::MAKE_COURSE_CALENDAR_EVENTS);
    }
    $calendar_events = $event->getCalendarEvents();

    $deviation_list = [];
    $days = $event->getDays();
    foreach ($days as $day) {
      $day_string = $day->format('Y-m-d');
      $deviation_list[$day_string] = [];
    }

    if (!empty($calendar_events)) {
      $class_id = $course->get('field_class')->target_id;
      $school_week_by_class = $class_id
        ? $this->schoolWeekService->getSchoolWeekByClassId($class_id)
        : NULL;
      if ($school_week_by_class) {
        $school_weeks = [$school_week_by_class];
      }
      else {
        $student_ids = array_column($course->get('field_student')->getValue(), 'target_id');
        $school_weeks = $this->schoolWeekService->getSchoolWeeksRelevantForUsers($student_ids);
      }

      foreach ($school_weeks as $school_week) {
        $deviation_map = $this->schoolWeekService->getSchoolWeekDeviationMap($school_week);
        foreach ($days as $day) {
          $day_string = $day->format('Y-m-d');
          if (!empty($deviation_map[$day_string])) {
            $deviation_list[$day_string][] = $deviation_map[$day_string];
          }
        }
      }
    }

    $cid = $this->warmUpStoredCalendarEventsCache($course_id, $sync_from, $sync_to, $is_bulk_action);
    $stored_calendar_event_data = &$this->lookup[$cid][$course_id];

    /** @var \Drupal\simple_school_reports_entities\CalendarEventInterface[] $calendar_event_to_save */
    $calendar_event_to_save = [];
    /** @var \Drupal\simple_school_reports_entities\CalendarEventInterface[] $calendar_event_to_delete */
    $calendar_event_to_delete = [];

    $calender_identifiers_to_keep = [];
    foreach ($calendar_events as $key => $calendar_event) {
      $event_from = $calendar_event->get('from')->value;
      $event_to = $calendar_event->get('to')->value;
      $base_date = new \DateTime();
      $base_date->setTimestamp($from);
      $base_date->setTime(0, 0, 0);
      $base_date_string = $base_date->format('Y-m-d');

      $keep = TRUE;
      foreach ($deviation_list[$base_date_string] as $deviation) {
        if ($deviation['no_teaching']) {
          $keep = FALSE;
          break;
        }

        $school_day_from = $deviation['from'] ?? NULL;
        $school_day_to = $deviation['to'] ?? NULL;
        if (!$school_day_from || !$school_day_to) {
          continue;
        }

        $school_day_from += $base_date->getTimestamp();
        $school_day_to += $base_date->getTimestamp();

        // If calendar event is outside of school day, do not keep.
        if ($to <= $school_day_from || $from >= $school_day_to) {
          $keep = FALSE;
          break;
        }

        $event_from = max($event_from, $school_day_from);
        $event_to = min($event_to, $school_day_to);
        $calendar_event->set('from', $event_from);
        $calendar_event->set('to', $event_to);
      }
      if (!$keep) {
        unset($calendar_events[$key]);
        continue;
      }

      $identifier = $this->calculateCourseCalendarEventIdentifier($calendar_event);
      $calender_identifiers_to_keep[$identifier] = $identifier;
      if (!empty($stored_calendar_event_data[$identifier])) {
        continue;
      }
      $calendar_event_to_save[$identifier] = $calendar_event;
    }

    foreach ($stored_calendar_event_data as $identifier => $stored_calendar_event) {
      if (!empty($calender_identifiers_to_keep[$identifier])) {
        continue;
      }
      if (!$stored_calendar_event['cancelled'] && !$stored_calendar_event['completed']) {
        $calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($stored_calendar_event['id']);
        $calendar_event_to_delete[$identifier] = $calendar_event;
      }
    }

    foreach ($calendar_event_to_save as $calendar_event) {
      $identifier = $calendar_event->get('identifier')->value;
      $calendar_event->save();
      $stored_calendar_event_data[$identifier] = [
        'id' => $calendar_event->id(),
        'completed' => FALSE,
        'cancelled' => FALSE,
      ];
    }

    foreach ($calendar_event_to_delete as $calendar_event) {
      $identifier = $calendar_event->get('identifier')->value;
      $calendar_event->delete();
      unset($stored_calendar_event_data[$identifier]);
    }
  }

  public function clearLookup(): void {
    $this->lookup = [];
  }

  public function removeCalendarEventsForCourse(string|int $course_id, int $from, int $to, bool $is_bulk_action): void {
    if (!ssr_use_schema()) {
      return;
    }
    $cid = $this->warmUpStoredCalendarEventsCache($course_id, $from, $to, $is_bulk_action);
    $stored_calendar_event_data = &$this->lookup[$cid][$course_id];

    /** @var \Drupal\simple_school_reports_entities\CalendarEventInterface[] $calendar_event_to_delete */
    foreach ($stored_calendar_event_data as $identifier => $stored_calendar_event) {
      if (!$stored_calendar_event['cancelled'] && !$stored_calendar_event['completed']) {
        $calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($stored_calendar_event['id']);
        $calendar_event?->delete();
        unset($stored_calendar_event_data[$identifier]);
      }
    }
  }
}
