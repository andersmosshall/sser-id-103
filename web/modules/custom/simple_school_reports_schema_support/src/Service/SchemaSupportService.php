<?php

namespace Drupal\simple_school_reports_schema_support\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_school_reports_core\Service\CourseServiceInterface;
use Drupal\simple_school_reports_entities\CalendarEventInterface;

/**
 *
 */
class SchemaSupportService implements SchemaSupportServiceInterface {

  protected array $lookup = [];

  public function __construct(
    protected CourseServiceInterface $courseService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function resolveCalenderEventName(CalendarEventInterface $calendar_event, bool $include_course_name = TRUE): string {
    $cid = 'event_name:' . $calendar_event->id() . ':' . ($include_course_name ? '1' : '0');
    if (isset($this->lookup[$cid])) {
      return $this->lookup[$cid];
    }

    $name = $calendar_event->label();
    if ($calendar_event->bundle() !== 'course') {
      return $name;
    }

    /** @var \Drupal\node\NodeInterface $course */
    $course = $calendar_event->get('field_course')->entity;
    if (!$course) {
      $this->lookup[$cid] = $name;
      return $name;
    }

    if ($include_course_name) {
      $name .= ' - ' . $course->label();
    }

    $sub_group = $calendar_event->get('field_course_sub_group')->value ?? 'default';
    $sub_group_name = $this->courseService->getSubGroupName($course->id(), $sub_group);
    if (!empty($sub_group_name)) {
      $name .= ' (' . $sub_group_name . ')';
    }

    $this->lookup[$cid] = $name;
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getStudentIds(CalendarEventInterface $calendar_event): array {
    if ($calendar_event->bundle() !== 'course') {
      return [];
    }
    $course_id = $calendar_event->get('field_course')->target_id;
    $sub_group = $calendar_event->get('field_course_sub_group')->value ?? 'default';

    if (!$course_id) {
      return [];
    }

    return $this->courseService->getStudentIdsInCourse($course_id, $sub_group);
  }

  /**
   * {@inheritdoc}
   */
  public function getStudentSchemaId(string $student_id): ?string {
    return $this->courseService->getStudentSchemaEntryDataIdentifiersHash($student_id);
  }

}
