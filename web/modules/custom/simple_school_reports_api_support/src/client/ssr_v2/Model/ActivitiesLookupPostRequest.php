<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class ActivitiesLookupPostRequest extends \ArrayObject
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
     * Hämta aktiviteter där attributet `teachers` inkluderar något av de angivna idn i `duty.id`.
     *
     * @var list<string>
     */
    protected $teachers;
    /**
     * Hämta aktiviteter där attributet `groups` inkluderar en grupp som matchar ett av angivna idn utifrån `groupMemberships.person.id`.
     *
     * @var list<string>
     */
    protected $members;
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
     * Hämta aktiviteter där attributet `teachers` inkluderar något av de angivna idn i `duty.id`.
     *
     * @return list<string>
     */
    public function getTeachers(): array
    {
        return $this->teachers;
    }
    /**
     * Hämta aktiviteter där attributet `teachers` inkluderar något av de angivna idn i `duty.id`.
     *
     * @param list<string> $teachers
     *
     * @return self
     */
    public function setTeachers(array $teachers): self
    {
        $this->initialized['teachers'] = true;
        $this->teachers = $teachers;
        return $this;
    }
    /**
     * Hämta aktiviteter där attributet `groups` inkluderar en grupp som matchar ett av angivna idn utifrån `groupMemberships.person.id`.
     *
     * @return list<string>
     */
    public function getMembers(): array
    {
        return $this->members;
    }
    /**
     * Hämta aktiviteter där attributet `groups` inkluderar en grupp som matchar ett av angivna idn utifrån `groupMemberships.person.id`.
     *
     * @param list<string> $members
     *
     * @return self
     */
    public function setMembers(array $members): self
    {
        $this->initialized['members'] = true;
        $this->members = $members;
        return $this;
    }
}