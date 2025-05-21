<?php

namespace Drupal\simple_school_reports_schema_support\Events;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\simple_school_reports_core\Service\ExportUsersServiceInterface;
use Drupal\simple_school_reports_entities\CalendarEventInterface;
use Drupal\simple_school_reports_schema_support\CourseCalendarEventTrait;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event to create calendar events for a course.
 */
class MakeCourseCalendarEvent extends Event {

  use CourseCalendarEventTrait;

  public function __construct(
    /**
     * The course id.
     */
    protected string|int $courseId,
    /**
     * From timestamp.
     */
    protected int $from,
    /**
     * To timestamp.
     */
    protected int $to,
    /**
     * If the action is a bulk action, e.g. think of loading anc cache
     * performance.
     */
    protected bool $isBulkAction,
    /**
     * @var CalendarEventInterface[] $calendarEvents
     *   Keyed by identifier.
     */
    protected array $calendarEvents = [],
  ) {}

  /**
   * @return string|int
   */
  public function getCourseId(): string|int {
    return $this->courseId;
  }

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCourse(): ContentEntityInterface | null {
    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $course */
    $course = \Drupal::entityTypeManager()->getStorage('node')->load($this->courseId);
    if (!$course) {
      return NULL;
    }
    return $course;
  }

  /**
   * @return bool
   */
  public function isBulkAction(): bool {
    return $this->isBulkAction;
  }

  /**
   * @return \Drupal\simple_school_reports_entities\CalendarEventInterface[]
   */
  public function getCalendarEvents(): array {
    return $this->calendarEvents;
  }

  /**
   * @return int
   */
  public function getFrom(): int {
    if ($this->from > $this->to) {
      return $this->to;
    }
    return $this->from;
  }

  /**
   * @return int
   */
  public function getTo(): int {
    if ($this->from > $this->to) {
      return $this->from;
    }
    return $this->to;
  }

  /**
   * @return \DateTime[];
   */
  public function getDays(): array {
    $from = $this->getFrom();
    $to = $this->getTo();
    $days = [];

    $day = new \DateTime();
    $day->setTimestamp($from);
    $day->setTime(12, 0, 0);

    $max_days = 365;

    while ($day->getTimestamp() <= $to && $max_days > 0) {
      $days[] = clone $day;
      $day->modify('+1 day');
      $max_days--;
    }

    return $days;
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $calendar_event
   *
   * @return $this
   */
  public function addCalendarEvent(CalendarEventInterface $calendar_event): self {
    $identifier = $this->calculateCourseCalendarEventIdentifier($calendar_event);
    if (!$identifier) {
      return $this;
    }
    if ($calendar_event->bundle() !== 'course') {
      throw new \InvalidArgumentException('Calendar event must be a course.');
    }
    $this->calendarEvents[$identifier] = $calendar_event;
    return $this;
  }

}
