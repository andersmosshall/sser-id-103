<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class ActivityExpandedAllOfEmbedded extends \ArrayObject
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
     * @var list<Group>
     */
    protected $groups;
    /**
     * Används för att referera till en specifik kurskod eller ett ämne med information om årskurs och skolform som avses med undervisningen. För officiella ämnen/kurser anges läroplan.
     *
     * @var Syllabus
     */
    protected $syllabus;
    /**
     * 
     *
     * @var list<Duty>
     */
    protected $teachers;
    /**
     * 
     *
     * @return list<Group>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
    /**
     * 
     *
     * @param list<Group> $groups
     *
     * @return self
     */
    public function setGroups(array $groups): self
    {
        $this->initialized['groups'] = true;
        $this->groups = $groups;
        return $this;
    }
    /**
     * Används för att referera till en specifik kurskod eller ett ämne med information om årskurs och skolform som avses med undervisningen. För officiella ämnen/kurser anges läroplan.
     *
     * @return Syllabus
     */
    public function getSyllabus(): Syllabus
    {
        return $this->syllabus;
    }
    /**
     * Används för att referera till en specifik kurskod eller ett ämne med information om årskurs och skolform som avses med undervisningen. För officiella ämnen/kurser anges läroplan.
     *
     * @param Syllabus $syllabus
     *
     * @return self
     */
    public function setSyllabus(Syllabus $syllabus): self
    {
        $this->initialized['syllabus'] = true;
        $this->syllabus = $syllabus;
        return $this;
    }
    /**
     * 
     *
     * @return list<Duty>
     */
    public function getTeachers(): array
    {
        return $this->teachers;
    }
    /**
     * 
     *
     * @param list<Duty> $teachers
     *
     * @return self
     */
    public function setTeachers(array $teachers): self
    {
        $this->initialized['teachers'] = true;
        $this->teachers = $teachers;
        return $this;
    }
}