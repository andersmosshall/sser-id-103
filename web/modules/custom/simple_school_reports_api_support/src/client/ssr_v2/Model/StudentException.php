<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class StudentException extends \ArrayObject
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
     * @var PersonReference
     */
    protected $student;
    /**
     * Används för att ange om en elev deltar på ett visst kalendertillfälle.
     *
     * @var bool
     */
    protected $participates;
    /**
     * Starttid för undantaget (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @var \DateTime
     */
    protected $startTime;
    /**
     * Sluttid för undantaget (RFC 3339 format tex "2015-12-12T11:00:00+01:00").
     *
     * @var \DateTime
     */
    protected $endTime;
    /**
     * Undervisningstid i minuter för eleven. Om den ej anges så gäller det som är angivet i, i första hand, CalendarEvent, och annars i Activity.
     *
     * @var int
     */
    protected $teachingLength;
    /**
     * 
     *
     * @return PersonReference
     */
    public function getStudent(): PersonReference
    {
        return $this->student;
    }
    /**
     * 
     *
     * @param PersonReference $student
     *
     * @return self
     */
    public function setStudent(PersonReference $student): self
    {
        $this->initialized['student'] = true;
        $this->student = $student;
        return $this;
    }
    /**
     * Används för att ange om en elev deltar på ett visst kalendertillfälle.
     *
     * @return bool
     */
    public function getParticipates(): bool
    {
        return $this->participates;
    }
    /**
     * Används för att ange om en elev deltar på ett visst kalendertillfälle.
     *
     * @param bool $participates
     *
     * @return self
     */
    public function setParticipates(bool $participates): self
    {
        $this->initialized['participates'] = true;
        $this->participates = $participates;
        return $this;
    }
    /**
     * Starttid för undantaget (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @return \DateTime
     */
    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }
    /**
     * Starttid för undantaget (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
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
     * Sluttid för undantaget (RFC 3339 format tex "2015-12-12T11:00:00+01:00").
     *
     * @return \DateTime
     */
    public function getEndTime(): \DateTime
    {
        return $this->endTime;
    }
    /**
     * Sluttid för undantaget (RFC 3339 format tex "2015-12-12T11:00:00+01:00").
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
     * Undervisningstid i minuter för eleven. Om den ej anges så gäller det som är angivet i, i första hand, CalendarEvent, och annars i Activity.
     *
     * @return int
     */
    public function getTeachingLength(): int
    {
        return $this->teachingLength;
    }
    /**
     * Undervisningstid i minuter för eleven. Om den ej anges så gäller det som är angivet i, i första hand, CalendarEvent, och annars i Activity.
     *
     * @param int $teachingLength
     *
     * @return self
     */
    public function setTeachingLength(int $teachingLength): self
    {
        $this->initialized['teachingLength'] = true;
        $this->teachingLength = $teachingLength;
        return $this;
    }
}