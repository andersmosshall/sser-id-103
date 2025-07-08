<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class CalendarEventsLookupPostRequest extends \ArrayObject
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
    protected $student;
    /**
     * 
     *
     * @var list<string>
     */
    protected $teacher;
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
    public function getStudent(): array
    {
        return $this->student;
    }
    /**
     * 
     *
     * @param list<string> $student
     *
     * @return self
     */
    public function setStudent(array $student): self
    {
        $this->initialized['student'] = true;
        $this->student = $student;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getTeacher(): array
    {
        return $this->teacher;
    }
    /**
     * 
     *
     * @param list<string> $teacher
     *
     * @return self
     */
    public function setTeacher(array $teacher): self
    {
        $this->initialized['teacher'] = true;
        $this->teacher = $teacher;
        return $this;
    }
}