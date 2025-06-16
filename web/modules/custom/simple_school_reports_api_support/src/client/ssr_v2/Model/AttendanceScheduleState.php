<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AttendanceScheduleState extends \ArrayObject
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
     * Beskriver schemats tillstånd.
     *
     * @var string
     */
    protected $state;
    /**
     * Tid och datum för tillstånd (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *
     * @var \DateTime
     */
    protected $registeredAt;
    /**
     * En kommentar angående tillståndet.
     *
     * @var string
     */
    protected $comment;
    /**
     * 
     *
     * @var mixed
     */
    protected $registeredBy;
    /**
     * Beskriver schemats tillstånd.
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }
    /**
     * Beskriver schemats tillstånd.
     *
     * @param string $state
     *
     * @return self
     */
    public function setState(string $state): self
    {
        $this->initialized['state'] = true;
        $this->state = $state;
        return $this;
    }
    /**
     * Tid och datum för tillstånd (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *
     * @return \DateTime
     */
    public function getRegisteredAt(): \DateTime
    {
        return $this->registeredAt;
    }
    /**
     * Tid och datum för tillstånd (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *
     * @param \DateTime $registeredAt
     *
     * @return self
     */
    public function setRegisteredAt(\DateTime $registeredAt): self
    {
        $this->initialized['registeredAt'] = true;
        $this->registeredAt = $registeredAt;
        return $this;
    }
    /**
     * En kommentar angående tillståndet.
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
    /**
     * En kommentar angående tillståndet.
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