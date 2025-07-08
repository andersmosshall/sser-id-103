<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Absence extends \ArrayObject
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
     * Identifierare för den anmälda frånvaron.
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
     * Starttid för den anmälda frånvaron (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *
     * @var \DateTime
     */
    protected $startTime;
    /**
     * Sluttid för den anmälda frånvaron (RFC 3339, format tex "2015-12-12T10:30:00+01:00").
     *
     * @var \DateTime
     */
    protected $endTime;
    /**
     * Anger om frånvaron är en beviljad ledighet eller en annan typ av anmäld frånvaro.
     *
     * @var string
     */
    protected $type;
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
    protected $organisation;
    /**
     * 
     *
     * @var mixed
     */
    protected $registeredBy;
    /**
     * Identifierare för den anmälda frånvaron.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Identifierare för den anmälda frånvaron.
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
     * Starttid för den anmälda frånvaron (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *
     * @return \DateTime
     */
    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }
    /**
     * Starttid för den anmälda frånvaron (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
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
     * Sluttid för den anmälda frånvaron (RFC 3339, format tex "2015-12-12T10:30:00+01:00").
     *
     * @return \DateTime
     */
    public function getEndTime(): \DateTime
    {
        return $this->endTime;
    }
    /**
     * Sluttid för den anmälda frånvaron (RFC 3339, format tex "2015-12-12T10:30:00+01:00").
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
     * Anger om frånvaron är en beviljad ledighet eller en annan typ av anmäld frånvaro.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    /**
     * Anger om frånvaron är en beviljad ledighet eller en annan typ av anmäld frånvaro.
     *
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->initialized['type'] = true;
        $this->type = $type;
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
    public function getOrganisation()
    {
        return $this->organisation;
    }
    /**
     * 
     *
     * @param mixed $organisation
     *
     * @return self
     */
    public function setOrganisation($organisation): self
    {
        $this->initialized['organisation'] = true;
        $this->organisation = $organisation;
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
}