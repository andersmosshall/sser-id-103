<?php

namespace Drupal\simple_school_reports_schema_support;

use Drupal\simple_school_reports_entities\CalendarEventInterface;

trait CourseCalendarEventTrait {

  protected function calculateCourseCalendarEventIdentifier(CalendarEventInterface $calendar_event, bool $update_field = TRUE): ?string {
    if ($calendar_event->bundle() !== 'course') {
      return 'unsupported';
    }

    $course_id = $calendar_event->get('field_course')->target_id ?? NULL;
    $course_sub_group = $calendar_event->get('field_course_sub_group')->value ?? 'default';
    $from = $calendar_event->get('from')->value ?? NULL;
    $to = $calendar_event->get('to')->value ?? NULL;

    if (!$course_id || !$from || !$to) {
      return NULL;
    }

    $identifier = $course_id . '-' . $course_sub_group . '-' . $from . '-' . $to;
    if ($update_field) {
      $calendar_event->set('identifier', $identifier);
    }
    return $identifier;
  }

}

