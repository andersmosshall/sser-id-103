<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class DutyAssignmentRoleInner extends \ArrayObject
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
     * @var GroupReference
     */
    protected $group;
    /**
     * 
     *
     * @var string
     */
    protected $assignmentRoleType;
    /**
     * Startdatum för tjänstens relation till gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för tjänstens relation till gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * 
     *
     * @return GroupReference
     */
    public function getGroup(): GroupReference
    {
        return $this->group;
    }
    /**
     * 
     *
     * @param GroupReference $group
     *
     * @return self
     */
    public function setGroup(GroupReference $group): self
    {
        $this->initialized['group'] = true;
        $this->group = $group;
        return $this;
    }
    /**
     * 
     *
     * @return string
     */
    public function getAssignmentRoleType(): string
    {
        return $this->assignmentRoleType;
    }
    /**
     * 
     *
     * @param string $assignmentRoleType
     *
     * @return self
     */
    public function setAssignmentRoleType(string $assignmentRoleType): self
    {
        $this->initialized['assignmentRoleType'] = true;
        $this->assignmentRoleType = $assignmentRoleType;
        return $this;
    }
    /**
     * Startdatum för tjänstens relation till gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för tjänstens relation till gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Slutdatum för tjänstens relation till gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för tjänstens relation till gruppen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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