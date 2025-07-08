<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PersonExpandedAllOfEmbeddedGroupMemberships extends \ArrayObject
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
     * Group kan innehålla personer eller bara vara en tom "platshållare" utan medlemmar, som kan populeras vid ett senare tillfälle. Notera att gruppens koppling till ämnen/kurser och lärare görs via Aktivitet. Grupper har olika egenskaper baserat på grupptyp. Individer kan ha olika roller i relation till en viss grupp. Grupper har specifika egenskaper.
     *
     * @var GroupFragment
     */
    protected $group;
    /**
     * Startdatum för personens medlemskap i gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för personens medlemskap i gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
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
    /**
     * Startdatum för personens medlemskap i gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för personens medlemskap i gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @param \DateTime $startDate
     *
     * @return self
     */
    public function setStartDate(\DateTime $startDate): self
    {
        $this->initialized['startDate'] = true;
        $this->startDate = $startDate;
        return $this;
    }
    /**
     * Slutdatum för personens medlemskap i gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för personens medlemskap i gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @param \DateTime $endDate
     *
     * @return self
     */
    public function setEndDate(\DateTime $endDate): self
    {
        $this->initialized['endDate'] = true;
        $this->endDate = $endDate;
        return $this;
    }
}