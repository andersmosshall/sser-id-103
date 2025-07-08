<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AttendanceSchedule extends \ArrayObject
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
     * Identifierare för vistelseschemat.
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
    protected $placement;
    /**
     * Hur många veckor schemat gäller för innan det "börjar om".
     *
     * @var int
     */
    protected $numberOfWeeks;
    /**
     * Anger datum då schemat startar (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Anger datum då schemat slutar (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * Anger om detta är ett undantag som gäller i stället för normalschemat under en begränsad tid. Slutdatum måste anges.
     *
     * @var bool
     */
    protected $temporary = false;
    /**
     * 
     *
     * @var list<AttendanceScheduleState>
     */
    protected $state;
    /**
     * 
     *
     * @var list<AttendanceScheduleEntry>
     */
    protected $scheduleEntries;
    /**
     * Identifierare för vistelseschemat.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Identifierare för vistelseschemat.
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
    public function getPlacement()
    {
        return $this->placement;
    }
    /**
     * 
     *
     * @param mixed $placement
     *
     * @return self
     */
    public function setPlacement($placement): self
    {
        $this->initialized['placement'] = true;
        $this->placement = $placement;
        return $this;
    }
    /**
     * Hur många veckor schemat gäller för innan det "börjar om".
     *
     * @return int
     */
    public function getNumberOfWeeks(): int
    {
        return $this->numberOfWeeks;
    }
    /**
     * Hur många veckor schemat gäller för innan det "börjar om".
     *
     * @param int $numberOfWeeks
     *
     * @return self
     */
    public function setNumberOfWeeks(int $numberOfWeeks): self
    {
        $this->initialized['numberOfWeeks'] = true;
        $this->numberOfWeeks = $numberOfWeeks;
        return $this;
    }
    /**
     * Anger datum då schemat startar (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Anger datum då schemat startar (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Anger datum då schemat slutar (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Anger datum då schemat slutar (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Anger om detta är ett undantag som gäller i stället för normalschemat under en begränsad tid. Slutdatum måste anges.
     *
     * @return bool
     */
    public function getTemporary(): bool
    {
        return $this->temporary;
    }
    /**
     * Anger om detta är ett undantag som gäller i stället för normalschemat under en begränsad tid. Slutdatum måste anges.
     *
     * @param bool $temporary
     *
     * @return self
     */
    public function setTemporary(bool $temporary): self
    {
        $this->initialized['temporary'] = true;
        $this->temporary = $temporary;
        return $this;
    }
    /**
     * 
     *
     * @return list<AttendanceScheduleState>
     */
    public function getState(): array
    {
        return $this->state;
    }
    /**
     * 
     *
     * @param list<AttendanceScheduleState> $state
     *
     * @return self
     */
    public function setState(array $state): self
    {
        $this->initialized['state'] = true;
        $this->state = $state;
        return $this;
    }
    /**
     * 
     *
     * @return list<AttendanceScheduleEntry>
     */
    public function getScheduleEntries(): array
    {
        return $this->scheduleEntries;
    }
    /**
     * 
     *
     * @param list<AttendanceScheduleEntry> $scheduleEntries
     *
     * @return self
     */
    public function setScheduleEntries(array $scheduleEntries): self
    {
        $this->initialized['scheduleEntries'] = true;
        $this->scheduleEntries = $scheduleEntries;
        return $this;
    }
}