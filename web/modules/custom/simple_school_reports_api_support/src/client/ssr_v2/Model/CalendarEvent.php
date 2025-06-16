<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class CalendarEvent extends \ArrayObject
{
    /**
     * @var array
     */
    protected $initialized = [];
    public function isInitialized($property): bool
    {
        return array_key_exists($property, $this->initialized);
    }
    /**
     * Identifierare för kalenderhändelsen.
     *
     * @var string
     */
    protected $id;
    /**
     * 
     *
     * @var Meta
     */
    protected $meta;
    /**
     * 
     *
     * @var mixed
     */
    protected $activity;
    /**
     * Kalenderhändelsens starttid med datum och tid (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @var \DateTime
     */
    protected $startTime;
    /**
     * Kalenderhändelsens sluttid med datum och tid (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @var \DateTime
     */
    protected $endTime;
    /**
     * Inställd används för att ange att en planerad kalenderhändelse inte ska äga rum till följd av en avbokning eller annan tillfällig avvikelse. Förvalt värde är False.
     *
     * @var bool
     */
    protected $cancelled;
    /**
     * Faktisk undervisningstid för lärare anges i minuter. Lärartiden kan vara kortare eller längre än tiden för kalenderhändelsen.
     *
     * @var int
     */
    protected $teachingLengthTeacher;
    /**
     * Faktisk undervisningstid för elever (och elever ingående i grupper). Anges i minuter. Tiden kan vara kortare eller längre än tiden för kalenderhändelsen, till exempel då en rast ingår i tiden.
     *
     * @var int
     */
    protected $teachingLengthStudent;
    /**
     * En text med kompletterande information.
     *
     * @var string
     */
    protected $comment;
    /**
     * 
     *
     * @var list<StudentException>
     */
    protected $studentExceptions;
    /**
     * 
     *
     * @var list<TeacherException>
     */
    protected $teacherExceptions;
    /**
     * 
     *
     * @var list<CalendarEventRoomsInner>
     */
    protected $rooms;
    /**
     * 
     *
     * @var list<CalendarEventResourcesInner>
     */
    protected $resources;
    /**
     * 
     *
     * @var CalendarEventEmbedded
     */
    protected $embedded;
    /**
     * Identifierare för kalenderhändelsen.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Identifierare för kalenderhändelsen.
     *
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): self
    {
        $this->initialized['id'] = true;
        $this->id = $id;
        return $this;
    }
    /**
     * 
     *
     * @return Meta
     */
    public function getMeta(): Meta
    {
        return $this->meta;
    }
    /**
     * 
     *
     * @param Meta $meta
     *
     * @return self
     */
    public function setMeta(Meta $meta): self
    {
        $this->initialized['meta'] = true;
        $this->meta = $meta;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getActivity()
    {
        return $this->activity;
    }
    /**
     * 
     *
     * @param mixed $activity
     *
     * @return self
     */
    public function setActivity($activity): self
    {
        $this->initialized['activity'] = true;
        $this->activity = $activity;
        return $this;
    }
    /**
     * Kalenderhändelsens starttid med datum och tid (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @return \DateTime
     */
    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }
    /**
     * Kalenderhändelsens starttid med datum och tid (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @param \DateTime $startTime
     *
     * @return self
     */
    public function setStartTime(\DateTime $startTime): self
    {
        $this->initialized['startTime'] = true;
        $this->startTime = $startTime;
        return $this;
    }
    /**
     * Kalenderhändelsens sluttid med datum och tid (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @return \DateTime
     */
    public function getEndTime(): \DateTime
    {
        return $this->endTime;
    }
    /**
     * Kalenderhändelsens sluttid med datum och tid (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @param \DateTime $endTime
     *
     * @return self
     */
    public function setEndTime(\DateTime $endTime): self
    {
        $this->initialized['endTime'] = true;
        $this->endTime = $endTime;
        return $this;
    }
    /**
     * Inställd används för att ange att en planerad kalenderhändelse inte ska äga rum till följd av en avbokning eller annan tillfällig avvikelse. Förvalt värde är False.
     *
     * @return bool
     */
    public function getCancelled(): bool
    {
        return $this->cancelled;
    }
    /**
     * Inställd används för att ange att en planerad kalenderhändelse inte ska äga rum till följd av en avbokning eller annan tillfällig avvikelse. Förvalt värde är False.
     *
     * @param bool $cancelled
     *
     * @return self
     */
    public function setCancelled(bool $cancelled): self
    {
        $this->initialized['cancelled'] = true;
        $this->cancelled = $cancelled;
        return $this;
    }
    /**
     * Faktisk undervisningstid för lärare anges i minuter. Lärartiden kan vara kortare eller längre än tiden för kalenderhändelsen.
     *
     * @return int
     */
    public function getTeachingLengthTeacher(): int
    {
        return $this->teachingLengthTeacher;
    }
    /**
     * Faktisk undervisningstid för lärare anges i minuter. Lärartiden kan vara kortare eller längre än tiden för kalenderhändelsen.
     *
     * @param int $teachingLengthTeacher
     *
     * @return self
     */
    public function setTeachingLengthTeacher(int $teachingLengthTeacher): self
    {
        $this->initialized['teachingLengthTeacher'] = true;
        $this->teachingLengthTeacher = $teachingLengthTeacher;
        return $this;
    }
    /**
     * Faktisk undervisningstid för elever (och elever ingående i grupper). Anges i minuter. Tiden kan vara kortare eller längre än tiden för kalenderhändelsen, till exempel då en rast ingår i tiden.
     *
     * @return int
     */
    public function getTeachingLengthStudent(): int
    {
        return $this->teachingLengthStudent;
    }
    /**
     * Faktisk undervisningstid för elever (och elever ingående i grupper). Anges i minuter. Tiden kan vara kortare eller längre än tiden för kalenderhändelsen, till exempel då en rast ingår i tiden.
     *
     * @param int $teachingLengthStudent
     *
     * @return self
     */
    public function setTeachingLengthStudent(int $teachingLengthStudent): self
    {
        $this->initialized['teachingLengthStudent'] = true;
        $this->teachingLengthStudent = $teachingLengthStudent;
        return $this;
    }
    /**
     * En text med kompletterande information.
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
    /**
     * En text med kompletterande information.
     *
     * @param string $comment
     *
     * @return self
     */
    public function setComment(string $comment): self
    {
        $this->initialized['comment'] = true;
        $this->comment = $comment;
        return $this;
    }
    /**
     * 
     *
     * @return list<StudentException>
     */
    public function getStudentExceptions(): array
    {
        return $this->studentExceptions;
    }
    /**
     * 
     *
     * @param list<StudentException> $studentExceptions
     *
     * @return self
     */
    public function setStudentExceptions(array $studentExceptions): self
    {
        $this->initialized['studentExceptions'] = true;
        $this->studentExceptions = $studentExceptions;
        return $this;
    }
    /**
     * 
     *
     * @return list<TeacherException>
     */
    public function getTeacherExceptions(): array
    {
        return $this->teacherExceptions;
    }
    /**
     * 
     *
     * @param list<TeacherException> $teacherExceptions
     *
     * @return self
     */
    public function setTeacherExceptions(array $teacherExceptions): self
    {
        $this->initialized['teacherExceptions'] = true;
        $this->teacherExceptions = $teacherExceptions;
        return $this;
    }
    /**
     * 
     *
     * @return list<CalendarEventRoomsInner>
     */
    public function getRooms(): array
    {
        return $this->rooms;
    }
    /**
     * 
     *
     * @param list<CalendarEventRoomsInner> $rooms
     *
     * @return self
     */
    public function setRooms(array $rooms): self
    {
        $this->initialized['rooms'] = true;
        $this->rooms = $rooms;
        return $this;
    }
    /**
     * 
     *
     * @return list<CalendarEventResourcesInner>
     */
    public function getResources(): array
    {
        return $this->resources;
    }
    /**
     * 
     *
     * @param list<CalendarEventResourcesInner> $resources
     *
     * @return self
     */
    public function setResources(array $resources): self
    {
        $this->initialized['resources'] = true;
        $this->resources = $resources;
        return $this;
    }
    /**
     * 
     *
     * @return CalendarEventEmbedded
     */
    public function getEmbedded(): CalendarEventEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param CalendarEventEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(CalendarEventEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}