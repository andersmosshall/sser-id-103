<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AttendancesLookupPostRequest extends \ArrayObject
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
     * @var list<string>
     */
    protected $ids;
    /**
     * 
     *
     * @var list<string>
     */
    protected $activities;
    /**
     * 
     *
     * @var list<string>
     */
    protected $students;
    /**
     * 
     *
     * @var list<string>
     */
    protected $calendareEvents;
    /**
     * 
     *
     * @return list<string>
     */
    public function getIds(): array
    {
        return $this->ids;
    }
    /**
     * 
     *
     * @param list<string> $ids
     *
     * @return self
     */
    public function setIds(array $ids): self
    {
        $this->initialized['ids'] = true;
        $this->ids = $ids;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getActivities(): array
    {
        return $this->activities;
    }
    /**
     * 
     *
     * @param list<string> $activities
     *
     * @return self
     */
    public function setActivities(array $activities): self
    {
        $this->initialized['activities'] = true;
        $this->activities = $activities;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getStudents(): array
    {
        return $this->students;
    }
    /**
     * 
     *
     * @param list<string> $students
     *
     * @return self
     */
    public function setStudents(array $students): self
    {
        $this->initialized['students'] = true;
        $this->students = $students;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getCalendareEvents(): array
    {
        return $this->calendareEvents;
    }
    /**
     * 
     *
     * @param list<string> $calendareEvents
     *
     * @return self
     */
    public function setCalendareEvents(array $calendareEvents): self
    {
        $this->initialized['calendareEvents'] = true;
        $this->calendareEvents = $calendareEvents;
        return $this;
    }
}