<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AttendanceEventEmbedded extends \ArrayObject
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
     * @var Person
     */
    protected $registeredBy;
    /**
     * 
     *
     * @var Person
     */
    protected $person;
    /**
     * Group kan innehålla personer eller bara vara en tom "platshållare" utan medlemmar, som kan populeras vid ett senare tillfälle. Notera att gruppens koppling till ämnen/kurser och lärare görs via Aktivitet. Grupper har olika egenskaper baserat på grupptyp. Individer kan ha olika roller i relation till en viss grupp. Grupper har specifika egenskaper.
     *
     * @var GroupFragment
     */
    protected $group;
    /**
     * 
     *
     * @return Person
     */
    public function getRegisteredBy(): Person
    {
        return $this->registeredBy;
    }
    /**
     * 
     *
     * @param Person $registeredBy
     *
     * @return self
     */
    public function setRegisteredBy(Person $registeredBy): self
    {
        $this->initialized['registeredBy'] = true;
        $this->registeredBy = $registeredBy;
        return $this;
    }
    /**
     * 
     *
     * @return Person
     */
    public function getPerson(): Person
    {
        return $this->person;
    }
    /**
     * 
     *
     * @param Person $person
     *
     * @return self
     */
    public function setPerson(Person $person): self
    {
        $this->initialized['person'] = true;
        $this->person = $person;
        return $this;
    }
    /**
     * Group kan innehålla personer eller bara vara en tom "platshållare" utan medlemmar, som kan populeras vid ett senare tillfälle. Notera att gruppens koppling till ämnen/kurser och lärare görs via Aktivitet. Grupper har olika egenskaper baserat på grupptyp. Individer kan ha olika roller i relation till en viss grupp. Grupper har specifika egenskaper.
     *
     * @return GroupFragment
     */
    public function getGroup(): GroupFragment
    {
        return $this->group;
    }
    /**
     * Group kan innehålla personer eller bara vara en tom "platshållare" utan medlemmar, som kan populeras vid ett senare tillfälle. Notera att gruppens koppling till ämnen/kurser och lärare görs via Aktivitet. Grupper har olika egenskaper baserat på grupptyp. Individer kan ha olika roller i relation till en viss grupp. Grupper har specifika egenskaper.
     *
     * @param GroupFragment $group
     *
     * @return self
     */
    public function setGroup(GroupFragment $group): self
    {
        $this->initialized['group'] = true;
        $this->group = $group;
        return $this;
    }
}