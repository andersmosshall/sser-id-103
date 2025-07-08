<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Attendance extends \ArrayObject
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
     * Identifierare för närvaroposten.
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
     * @var CalendarEventReference
     */
    protected $calendarEvent;
    /**
     * 
     *
     * @var mixed
     */
    protected $student;
    /**
     * 
     *
     * @var mixed
     */
    protected $reporter;
    /**
     * Anger om lektionen är rapporterad.
     *
     * @var bool
     */
    protected $isReported;
    /**
     * Längd i minuter för elevens närvaro på kalenderhändelsen.
     *
     * @var int
     */
    protected $attendanceMinutes;
    /**
     * Längd i minuter för elevens giltiga frånvaro på kalenderhändelsen.
     *
     * @var int
     */
    protected $validAbsenceMinutes;
    /**
     * Längd i minuter för elevens ogiltiga frånvaro på kalenderhändelsen.
     *
     * @var int
     */
    protected $invalidAbsenceMinutes;
    /**
     * Tid i  minuter för elevens deltagande i annan skolaktivitet, såsom elevråd, i stället för deltagande på kalenderhändelsen.
     *
     * @var int
     */
    protected $otherAttendanceMinutes;
    /**
     * Angiven anledning till frånvaro.
     *
     * @var string
     */
    protected $absenceReason;
    /**
     * Tidpunkt för rapportering av kalenderhändelsen (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *
     * @var \DateTime
     */
    protected $reportedTimestamp;
    /**
     * Identifierare för närvaroposten.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Identifierare för närvaroposten.
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
     * @return CalendarEventReference
     */
    public function getCalendarEvent(): CalendarEventReference
    {
        return $this->calendarEvent;
    }
    /**
     * 
     *
     * @param CalendarEventReference $calendarEvent
     *
     * @return self
     */
    public function setCalendarEvent(CalendarEventReference $calendarEvent): self
    {
        $this->initialized['calendarEvent'] = true;
        $this->calendarEvent = $calendarEvent;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getStudent()
    {
        return $this->student;
    }
    /**
     * 
     *
     * @param mixed $student
     *
     * @return self
     */
    public function setStudent($student): self
    {
        $this->initialized['student'] = true;
        $this->student = $student;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getReporter()
    {
        return $this->reporter;
    }
    /**
     * 
     *
     * @param mixed $reporter
     *
     * @return self
     */
    public function setReporter($reporter): self
    {
        $this->initialized['reporter'] = true;
        $this->reporter = $reporter;
        return $this;
    }
    /**
     * Anger om lektionen är rapporterad.
     *
     * @return bool
     */
    public function getIsReported(): bool
    {
        return $this->isReported;
    }
    /**
     * Anger om lektionen är rapporterad.
     *
     * @param bool $isReported
     *
     * @return self
     */
    public function setIsReported(bool $isReported): self
    {
        $this->initialized['isReported'] = true;
        $this->isReported = $isReported;
        return $this;
    }
    /**
     * Längd i minuter för elevens närvaro på kalenderhändelsen.
     *
     * @return int
     */
    public function getAttendanceMinutes(): int
    {
        return $this->attendanceMinutes;
    }
    /**
     * Längd i minuter för elevens närvaro på kalenderhändelsen.
     *
     * @param int $attendanceMinutes
     *
     * @return self
     */
    public function setAttendanceMinutes(int $attendanceMinutes): self
    {
        $this->initialized['attendanceMinutes'] = true;
        $this->attendanceMinutes = $attendanceMinutes;
        return $this;
    }
    /**
     * Längd i minuter för elevens giltiga frånvaro på kalenderhändelsen.
     *
     * @return int
     */
    public function getValidAbsenceMinutes(): int
    {
        return $this->validAbsenceMinutes;
    }
    /**
     * Längd i minuter för elevens giltiga frånvaro på kalenderhändelsen.
     *
     * @param int $validAbsenceMinutes
     *
     * @return self
     */
    public function setValidAbsenceMinutes(int $validAbsenceMinutes): self
    {
        $this->initialized['validAbsenceMinutes'] = true;
        $this->validAbsenceMinutes = $validAbsenceMinutes;
        return $this;
    }
    /**
     * Längd i minuter för elevens ogiltiga frånvaro på kalenderhändelsen.
     *
     * @return int
     */
    public function getInvalidAbsenceMinutes(): int
    {
        return $this->invalidAbsenceMinutes;
    }
    /**
     * Längd i minuter för elevens ogiltiga frånvaro på kalenderhändelsen.
     *
     * @param int $invalidAbsenceMinutes
     *
     * @return self
     */
    public function setInvalidAbsenceMinutes(int $invalidAbsenceMinutes): self
    {
        $this->initialized['invalidAbsenceMinutes'] = true;
        $this->invalidAbsenceMinutes = $invalidAbsenceMinutes;
        return $this;
    }
    /**
     * Tid i  minuter för elevens deltagande i annan skolaktivitet, såsom elevråd, i stället för deltagande på kalenderhändelsen.
     *
     * @return int
     */
    public function getOtherAttendanceMinutes(): int
    {
        return $this->otherAttendanceMinutes;
    }
    /**
     * Tid i  minuter för elevens deltagande i annan skolaktivitet, såsom elevråd, i stället för deltagande på kalenderhändelsen.
     *
     * @param int $otherAttendanceMinutes
     *
     * @return self
     */
    public function setOtherAttendanceMinutes(int $otherAttendanceMinutes): self
    {
        $this->initialized['otherAttendanceMinutes'] = true;
        $this->otherAttendanceMinutes = $otherAttendanceMinutes;
        return $this;
    }
    /**
     * Angiven anledning till frånvaro.
     *
     * @return string
     */
    public function getAbsenceReason(): string
    {
        return $this->absenceReason;
    }
    /**
     * Angiven anledning till frånvaro.
     *
     * @param string $absenceReason
     *
     * @return self
     */
    public function setAbsenceReason(string $absenceReason): self
    {
        $this->initialized['absenceReason'] = true;
        $this->absenceReason = $absenceReason;
        return $this;
    }
    /**
     * Tidpunkt för rapportering av kalenderhändelsen (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *
     * @return \DateTime
     */
    public function getReportedTimestamp(): \DateTime
    {
        return $this->reportedTimestamp;
    }
    /**
     * Tidpunkt för rapportering av kalenderhändelsen (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *
     * @param \DateTime $reportedTimestamp
     *
     * @return self
     */
    public function setReportedTimestamp(\DateTime $reportedTimestamp): self
    {
        $this->initialized['reportedTimestamp'] = true;
        $this->reportedTimestamp = $reportedTimestamp;
        return $this;
    }
}