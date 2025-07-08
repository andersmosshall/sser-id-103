<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class CalendarEventEmbedded extends \ArrayObject
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
     * @var list<Attendance>
     */
    protected $attendance;
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
     * @return list<Attendance>
     */
    public function getAttendance(): array
    {
        return $this->attendance;
    }
    /**
     * 
     *
     * @param list<Attendance> $attendance
     *
     * @return self
     */
    public function setAttendance(array $attendance): self
    {
        $this->initialized['attendance'] = true;
        $this->attendance = $attendance;
        return $this;
    }
}