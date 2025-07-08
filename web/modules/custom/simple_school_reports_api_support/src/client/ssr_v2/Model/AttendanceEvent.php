<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AttendanceEvent extends \ArrayObject
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
     * Identifierare för den anmälda närvaron.
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
     * @var \DateTime
     */
    protected $time;
    /**
     * 
     *
     * @var string
     */
    protected $eventType;
    /**
     * 
     *
     * @var mixed
     */
    protected $person;
    /**
     * 
     *
     * @var mixed
     */
    protected $registeredBy;
    /**
     * 
     *
     * @var mixed
     */
    protected $group;
    /**
     * 
     *
     * @var AttendanceEventEmbedded
     */
    protected $embedded;
    /**
     * Identifierare för den anmälda närvaron.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Identifierare för den anmälda närvaron.
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
     * @return \DateTime
     */
    public function getTime(): \DateTime
    {
        return $this->time;
    }
    /**
     * 
     *
     * @param \DateTime $time
     *
     * @return self
     */
    public function setTime(\DateTime $time): self
    {
        $this->initialized['time'] = true;
        $this->time = $time;
        return $this;
    }
    /**
     * 
     *
     * @return string
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }
    /**
     * 
     *
     * @param string $eventType
     *
     * @return self
     */
    public function setEventType(string $eventType): self
    {
        $this->initialized['eventType'] = true;
        $this->eventType = $eventType;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getPerson()
    {
        return $this->person;
    }
    /**
     * 
     *
     * @param mixed $person
     *
     * @return self
     */
    public function setPerson($person): self
    {
        $this->initialized['person'] = true;
        $this->person = $person;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getRegisteredBy()
    {
        return $this->registeredBy;
    }
    /**
     * 
     *
     * @param mixed $registeredBy
     *
     * @return self
     */
    public function setRegisteredBy($registeredBy): self
    {
        $this->initialized['registeredBy'] = true;
        $this->registeredBy = $registeredBy;
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
     * @return AttendanceEventEmbedded
     */
    public function getEmbedded(): AttendanceEventEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param AttendanceEventEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(AttendanceEventEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}