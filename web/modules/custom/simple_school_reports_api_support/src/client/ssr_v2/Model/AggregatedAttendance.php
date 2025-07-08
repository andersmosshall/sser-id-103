<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AggregatedAttendance extends \ArrayObject
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
     * 
     *
     * @var ActivityReference
     */
    protected $activity;
    /**
     * 
     *
     * @var mixed
     */
    protected $student;
    /**
     * Startdatum för den aggregerade närvaron (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för den aggregerade närvaron (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * Summerad tid i minuter för elevens närvaro på kalenderhändelser.
     *
     * @var int
     */
    protected $attendanceSum;
    /**
     * Summerad tid i minuter för elevens giltiga frånvaro på kalenderhändelser.
     *
     * @var int
     */
    protected $validAbsenceSum;
    /**
     * Summerad tid i minuter för elevens ogiltiga frånvaro på kalenderhändelser.
     *
     * @var int
     */
    protected $invalidAbsenceSum;
    /**
     * Summerad tid i minuter för elevens deltagande i annan skolaktivitet, såsom elevråd, i stället för deltagande på kalenderhändelser.
     *
     * @var int
     */
    protected $otherAttendanceSum;
    /**
     * Summerad tid i minuter för alla elevens kalenderhändelser där läraren eller annan personal har markerat lektionen som färdigrapporterad.
     *
     * @var int
     */
    protected $reportedSum;
    /**
     * Summerad tid i minuter för alla kalenderhändelser där eleven har erbjudits möjlighet att närvara.
     *
     * @var int
     */
    protected $offeredSum;
    /**
     * 
     *
     * @var AggregatedAttendanceEmbedded
     */
    protected $embedded;
    /**
     * 
     *
     * @return ActivityReference
     */
    public function getActivity(): ActivityReference
    {
        return $this->activity;
    }
    /**
     * 
     *
     * @param ActivityReference $activity
     *
     * @return self
     */
    public function setActivity(ActivityReference $activity): self
    {
        $this->initialized['activity'] = true;
        $this->activity = $activity;
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
     * Startdatum för den aggregerade närvaron (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för den aggregerade närvaron (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @param \DateTime $startDate
     *
     * @return self
     */
    public function setStartDate(\DateTime $startDate): self
    {
        $this->initialized['startDate'] = true;
        $this->startDate = $startDate;
        return $this;
    }
    /**
     * Slutdatum för den aggregerade närvaron (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för den aggregerade närvaron (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @param \DateTime $endDate
     *
     * @return self
     */
    public function setEndDate(\DateTime $endDate): self
    {
        $this->initialized['endDate'] = true;
        $this->endDate = $endDate;
        return $this;
    }
    /**
     * Summerad tid i minuter för elevens närvaro på kalenderhändelser.
     *
     * @return int
     */
    public function getAttendanceSum(): int
    {
        return $this->attendanceSum;
    }
    /**
     * Summerad tid i minuter för elevens närvaro på kalenderhändelser.
     *
     * @param int $attendanceSum
     *
     * @return self
     */
    public function setAttendanceSum(int $attendanceSum): self
    {
        $this->initialized['attendanceSum'] = true;
        $this->attendanceSum = $attendanceSum;
        return $this;
    }
    /**
     * Summerad tid i minuter för elevens giltiga frånvaro på kalenderhändelser.
     *
     * @return int
     */
    public function getValidAbsenceSum(): int
    {
        return $this->validAbsenceSum;
    }
    /**
     * Summerad tid i minuter för elevens giltiga frånvaro på kalenderhändelser.
     *
     * @param int $validAbsenceSum
     *
     * @return self
     */
    public function setValidAbsenceSum(int $validAbsenceSum): self
    {
        $this->initialized['validAbsenceSum'] = true;
        $this->validAbsenceSum = $validAbsenceSum;
        return $this;
    }
    /**
     * Summerad tid i minuter för elevens ogiltiga frånvaro på kalenderhändelser.
     *
     * @return int
     */
    public function getInvalidAbsenceSum(): int
    {
        return $this->invalidAbsenceSum;
    }
    /**
     * Summerad tid i minuter för elevens ogiltiga frånvaro på kalenderhändelser.
     *
     * @param int $invalidAbsenceSum
     *
     * @return self
     */
    public function setInvalidAbsenceSum(int $invalidAbsenceSum): self
    {
        $this->initialized['invalidAbsenceSum'] = true;
        $this->invalidAbsenceSum = $invalidAbsenceSum;
        return $this;
    }
    /**
     * Summerad tid i minuter för elevens deltagande i annan skolaktivitet, såsom elevråd, i stället för deltagande på kalenderhändelser.
     *
     * @return int
     */
    public function getOtherAttendanceSum(): int
    {
        return $this->otherAttendanceSum;
    }
    /**
     * Summerad tid i minuter för elevens deltagande i annan skolaktivitet, såsom elevråd, i stället för deltagande på kalenderhändelser.
     *
     * @param int $otherAttendanceSum
     *
     * @return self
     */
    public function setOtherAttendanceSum(int $otherAttendanceSum): self
    {
        $this->initialized['otherAttendanceSum'] = true;
        $this->otherAttendanceSum = $otherAttendanceSum;
        return $this;
    }
    /**
     * Summerad tid i minuter för alla elevens kalenderhändelser där läraren eller annan personal har markerat lektionen som färdigrapporterad.
     *
     * @return int
     */
    public function getReportedSum(): int
    {
        return $this->reportedSum;
    }
    /**
     * Summerad tid i minuter för alla elevens kalenderhändelser där läraren eller annan personal har markerat lektionen som färdigrapporterad.
     *
     * @param int $reportedSum
     *
     * @return self
     */
    public function setReportedSum(int $reportedSum): self
    {
        $this->initialized['reportedSum'] = true;
        $this->reportedSum = $reportedSum;
        return $this;
    }
    /**
     * Summerad tid i minuter för alla kalenderhändelser där eleven har erbjudits möjlighet att närvara.
     *
     * @return int
     */
    public function getOfferedSum(): int
    {
        return $this->offeredSum;
    }
    /**
     * Summerad tid i minuter för alla kalenderhändelser där eleven har erbjudits möjlighet att närvara.
     *
     * @param int $offeredSum
     *
     * @return self
     */
    public function setOfferedSum(int $offeredSum): self
    {
        $this->initialized['offeredSum'] = true;
        $this->offeredSum = $offeredSum;
        return $this;
    }
    /**
     * 
     *
     * @return AggregatedAttendanceEmbedded
     */
    public function getEmbedded(): AggregatedAttendanceEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param AggregatedAttendanceEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(AggregatedAttendanceEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}