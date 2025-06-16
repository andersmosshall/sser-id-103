<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class GroupAllOf extends \ArrayObject
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
     * Gruppmedlemskap anger att en person är medlem i en grupp och vilken tidsperiod medlemskapet omfattar.
     *
     * @var list<GroupMembership>
     */
    protected $groupMemberships;
    /**
     * Gruppmedlemskap anger att en person är medlem i en grupp och vilken tidsperiod medlemskapet omfattar.
     *
     * @return list<GroupMembership>
     */
    public function getGroupMemberships(): array
    {
        return $this->groupMemberships;
    }
    /**
     * Gruppmedlemskap anger att en person är medlem i en grupp och vilken tidsperiod medlemskapet omfattar.
     *
     * @param list<GroupMembership> $groupMemberships
     *
     * @return self
     */
    public function setGroupMemberships(array $groupMemberships): self
    {
        $this->initialized['groupMemberships'] = true;
        $this->groupMemberships = $groupMemberships;
        return $this;
    }
}