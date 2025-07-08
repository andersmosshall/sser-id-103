<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Placement extends \ArrayObject
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
    protected $placedAt;
    /**
     * 
     *
     * @var mixed
     */
    protected $group;
    /**
     * 
     *
     * @var mixed
     */
    protected $child;
    /**
     * En lista med identifierare för de personer som äger placeringen. Används primärt för att styra vilka som skall kunna se och lägga schema.
     *
     * @var list<PersonReference>
     */
    protected $owners;
    /**
     * Skolform för placeringen, förskola eller fritidshem
     *
     * @var string
     */
    protected $schoolType;
    /**
     * Startdatum för placeringen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för placeringen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * En kod för att beskriva orsak för placeringen.
     *
     * @var string
     */
    protected $reason;
    /**
     * Anger maximal schematid per vecka för barnets placering.
     *
     * @var int
     */
    protected $maxWeeklyScheduleHours;
    /**
     * 
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * 
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
    public function getPlacedAt()
    {
        return $this->placedAt;
    }
    /**
     * 
     *
     * @param mixed $placedAt
     *
     * @return self
     */
    public function setPlacedAt($placedAt): self
    {
        $this->initialized['placedAt'] = true;
        $this->placedAt = $placedAt;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }
    /**
     * 
     *
     * @param mixed $group
     *
     * @return self
     */
    public function setGroup($group): self
    {
        $this->initialized['group'] = true;
        $this->group = $group;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getChild()
    {
        return $this->child;
    }
    /**
     * 
     *
     * @param mixed $child
     *
     * @return self
     */
    public function setChild($child): self
    {
        $this->initialized['child'] = true;
        $this->child = $child;
        return $this;
    }
    /**
     * En lista med identifierare för de personer som äger placeringen. Används primärt för att styra vilka som skall kunna se och lägga schema.
     *
     * @return list<PersonReference>
     */
    public function getOwners(): array
    {
        return $this->owners;
    }
    /**
     * En lista med identifierare för de personer som äger placeringen. Används primärt för att styra vilka som skall kunna se och lägga schema.
     *
     * @param list<PersonReference> $owners
     *
     * @return self
     */
    public function setOwners(array $owners): self
    {
        $this->initialized['owners'] = true;
        $this->owners = $owners;
        return $this;
    }
    /**
     * Skolform för placeringen, förskola eller fritidshem
     *
     * @return string
     */
    public function getSchoolType(): string
    {
        return $this->schoolType;
    }
    /**
     * Skolform för placeringen, förskola eller fritidshem
     *
     * @param string $schoolType
     *
     * @return self
     */
    public function setSchoolType(string $schoolType): self
    {
        $this->initialized['schoolType'] = true;
        $this->schoolType = $schoolType;
        return $this;
    }
    /**
     * Startdatum för placeringen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för placeringen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Slutdatum för placeringen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för placeringen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * En kod för att beskriva orsak för placeringen.
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
    /**
     * En kod för att beskriva orsak för placeringen.
     *
     * @param string $reason
     *
     * @return self
     */
    public function setReason(string $reason): self
    {
        $this->initialized['reason'] = true;
        $this->reason = $reason;
        return $this;
    }
    /**
     * Anger maximal schematid per vecka för barnets placering.
     *
     * @return int
     */
    public function getMaxWeeklyScheduleHours(): int
    {
        return $this->maxWeeklyScheduleHours;
    }
    /**
     * Anger maximal schematid per vecka för barnets placering.
     *
     * @param int $maxWeeklyScheduleHours
     *
     * @return self
     */
    public function setMaxWeeklyScheduleHours(int $maxWeeklyScheduleHours): self
    {
        $this->initialized['maxWeeklyScheduleHours'] = true;
        $this->maxWeeklyScheduleHours = $maxWeeklyScheduleHours;
        return $this;
    }
}