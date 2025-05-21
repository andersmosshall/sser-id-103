<?php

namespace Drupal\simple_school_reports_schema_ssr\EventSubscriber;

use Drupal\autologout\AutologoutManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_school_reports_core\Service\CourseServiceInterface;
use Drupal\simple_school_reports_entities\CalendarEventInterface;
use Drupal\simple_school_reports_schema_support\Events\MakeCourseCalendarEvent;
use Drupal\simple_school_reports_schema_support\Events\SsrSchemaSupportEvents;
use Drupal\simple_school_reports_schema_support\Service\CalendarEventsSyncServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines MakeCourseCalendarEventSubscriber Subscriber.
 */
class MakeCourseCalendarEventSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected CourseServiceInterface $courseService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CalendarEventsSyncServiceInterface $calendarEventsSyncService,
  ) {}

  protected function supportedSource(): string {
    return 'ssr';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SsrSchemaSupportEvents::MAKE_COURSE_CALENDAR_EVENTS][] = 'onMakeCourseCalendarEvent';
    return $events;
  }

  /**
   * @param \Drupal\simple_school_reports_schema_support\Events\MakeCourseCalendarEvent $event
   *
   * @return void
   */
  public function onMakeCourseCalendarEvent(MakeCourseCalendarEvent $event) {
    $is_bulk_action = $event->isBulkAction();
    $days = $event->getDays();
    if (empty($days)) {
      return;
    }

    foreach ($this->courseService->getSchemaEntryData($event->getCourseId(), $is_bulk_action) as $schema_entry_data) {
      foreach ($days as $day) {
        $calendar_event = $this->makeCalendarEvent($event->getCourseId(), $schema_entry_data, $day);
        if ($calendar_event) {
          $event->addCalendarEvent($calendar_event);
        }
      }
    }
  }

  protected function makeCalendarEvent(string|int $course_id, array $data, \DateTime $day): ?CalendarEventInterface {
    $source = $data['source'] ?? NULL;
    if ($source !== $this->supportedSource()) {
      return NULL;
    }

    $from = $data['from'] ?? NULL;
    $to = $data['to'] ?? NULL;
    $week_day = $data['week_day'] ?? NULL;
    if (!$from || !$to || !$week_day) {
      return NULL;
    }

    if ($day->format('N') != $week_day) {
      return NULL;
    }

    $periodicity = $data['periodicity'] ?? 'weekly';
    $week_number = (int) $day->format('W');

    if ($periodicity === 'odd_weeks' && $week_number % 2 === 0) {
      return NULL;
    }
    if ($periodicity === 'even_weeks' && $week_number % 2 !== 0) {
      return NULL;
    }

    if ($periodicity === 'custom') {
      $periodicity_week = $data['periodicity_week'] ?? NULL;
      $periodicity_start_week = $data['periodicity_start_week'] ?? NULL;
      if (!$periodicity_week || !$periodicity_start_week) {
        return NULL;
      }

      $week_date_object = clone $day;
      $compare_week = (int) $week_date_object->format('W');
      if ($compare_week < 2) {
        $week_date_object->modify('+' . ($periodicity_week * 7) . ' days');
      }
      elseif ($compare_week > 50) {
        $week_date_object->modify('-' . ($periodicity_week * 7) . ' days');
      }
      $compare_week = (int) $week_date_object->format('Y') * 100 + (int) $week_date_object->format('W');
      $diff = abs($periodicity_start_week - $compare_week);
      if ($diff % $periodicity_week !== 0) {
        return NULL;
      }
    }

    $sub_group_id = $data['sub_group_id'] ?? 'default';

    $base_time = new \DateTime($day->format('Y-m-d') . ' 00:00:00');

    if ($from > $to) {
      $tmp = $to;
      $to = $from;
      $from = $tmp;
    }

    /** @var \Drupal\simple_school_reports_entities\Entity\CalendarEvent $calendar_event */
    $calendar_event =  $this->entityTypeManager->getStorage('ssr_calendar_event')->create([
      'status' => TRUE,
      'langcode' => 'sv',
      'bundle' => 'course',
      'from' => $base_time->getTimestamp() + $from,
      'to' => $base_time->getTimestamp() + $to,
      'field_course' => ['target_id' => $course_id],
      'field_course_sub_group' => $sub_group_id,
    ]);

    return $calendar_event;
  }

}
