<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class DutyAssignment extends \ArrayObject
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
     * @var DutyReference
     */
    protected $duty;
    /**
     * Datum för när lärarens deltagande i aktiviteten startar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Datum för när lärarens deltagande i aktiviteten slutar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * Det antal minuter läraren är kopplad till aktiviteten
     *
     * @var int
     */
    protected $minutesPlanned;
    /**
     * Markerar att läraren har rollen som betygsättande lärare för aktiviteten
     *
     * @var bool
     */
    protected $grader;
    /**
     * 
     *
     * @return DutyReference
     */
    public function getDuty(): DutyReference
    {
        return $this->duty;
    }
    /**
     * 
     *
     * @param DutyReference $duty
     *
     * @return self
     */
    public function setDuty(DutyReference $duty): self
    {
        $this->initialized['duty'] = true;
        $this->duty = $duty;
        return $this;
    }
    /**
     * Datum för när lärarens deltagande i aktiviteten startar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Datum för när lärarens deltagande i aktiviteten startar (RFC 3339-format, t.ex. "2016-10-15").
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
     * Datum för när lärarens deltagande i aktiviteten slutar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Datum för när lärarens deltagande i aktiviteten slutar (RFC 3339-format, t.ex. "2016-10-15").
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
     * Det antal minuter läraren är kopplad till aktiviteten
     *
     * @return int
     */
    public function getMinutesPlanned(): int
    {
        return $this->minutesPlanned;
    }
    /**
     * Det antal minuter läraren är kopplad till aktiviteten
     *
     * @param int $minutesPlanned
     *
     * @return self
     */
    public function setMinutesPlanned(int $minutesPlanned): self
    {
        $this->initialized['minutesPlanned'] = true;
        $this->minutesPlanned = $minutesPlanned;
        return $this;
    }
    /**
     * Markerar att läraren har rollen som betygsättande lärare för aktiviteten
     *
     * @return bool
     */
    public function getGrader(): bool
    {
        return $this->grader;
    }
    /**
     * Markerar att läraren har rollen som betygsättande lärare för aktiviteten
     *
     * @param bool $grader
     *
     * @return self
     */
    public function setGrader(bool $grader): self
    {
        $this->initialized['grader'] = true;
        $this->grader = $grader;
        return $this;
    }
}