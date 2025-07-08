<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class StudyPlanSyllabus extends \ArrayObject
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
     * @var SyllabusReference
     */
    protected $syllabus;
    /**
     * Notering angående kursens status i elevens studieplan
     *
     * @var string
     */
    protected $note;
    /**
     * Startdatum när eleven läser kursen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för när eleven läser kursen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * Planlagda timmar för elevens deltagande i kursen. Främst avsedd för studieplaner till vuxenutbildning.
     *
     * @var int
     */
    protected $hours;
    /**
     * 
     *
     * @return SyllabusReference
     */
    public function getSyllabus(): SyllabusReference
    {
        return $this->syllabus;
    }
    /**
     * 
     *
     * @param SyllabusReference $syllabus
     *
     * @return self
     */
    public function setSyllabus(SyllabusReference $syllabus): self
    {
        $this->initialized['syllabus'] = true;
        $this->syllabus = $syllabus;
        return $this;
    }
    /**
     * Notering angående kursens status i elevens studieplan
     *
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }
    /**
     * Notering angående kursens status i elevens studieplan
     *
     * @param string $note
     *
     * @return self
     */
    public function setNote(string $note): self
    {
        $this->initialized['note'] = true;
        $this->note = $note;
        return $this;
    }
    /**
     * Startdatum när eleven läser kursen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum när eleven läser kursen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Slutdatum för när eleven läser kursen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för när eleven läser kursen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Planlagda timmar för elevens deltagande i kursen. Främst avsedd för studieplaner till vuxenutbildning.
     *
     * @return int
     */
    public function getHours(): int
    {
        return $this->hours;
    }
    /**
     * Planlagda timmar för elevens deltagande i kursen. Främst avsedd för studieplaner till vuxenutbildning.
     *
     * @param int $hours
     *
     * @return self
     */
    public function setHours(int $hours): self
    {
        $this->initialized['hours'] = true;
        $this->hours = $hours;
        return $this;
    }
}