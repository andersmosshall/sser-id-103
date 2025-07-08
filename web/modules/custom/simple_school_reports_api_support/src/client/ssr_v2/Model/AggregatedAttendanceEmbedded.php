<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AggregatedAttendanceEmbedded extends \ArrayObject
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
     * @var Activity
     */
    protected $activity;
    /**
     * 
     *
     * @var Person
     */
    protected $student;
    /**
     * 
     *
     * @return Activity
     */
    public function getActivity(): Activity
    {
        return $this->activity;
    }
    /**
     * 
     *
     * @param Activity $activity
     *
     * @return self
     */
    public function setActivity(Activity $activity): self
    {
        $this->initialized['activity'] = true;
        $this->activity = $activity;
        return $this;
    }
    /**
     * 
     *
     * @return Person
     */
    public function getStudent(): Person
    {
        return $this->student;
    }
    /**
     * 
     *
     * @param Person $student
     *
     * @return self
     */
    public function setStudent(Person $student): self
    {
        $this->initialized['student'] = true;
        $this->student = $student;
        return $this;
    }
}