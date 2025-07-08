<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PersonExpandedAllOfEmbedded extends \ArrayObject
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
     * De barn/barnen vilka personen har ansvar för.
     *
     * @var list<PersonResponsiblesInner>
     */
    protected $responsibleFor;
    /**
     * En lista med placeringar för personen.
     *
     * @var list<Placement>
     */
    protected $placements;
    /**
     * En lista med placeringar där personen är satt som ägare.
     *
     * @var list<Placement>
     */
    protected $ownedPlacements;
    /**
     * Personens aktuella tjänstgöring
     *
     * @var list<Duty>
     */
    protected $duties;
    /**
     * En lista med grupper där personen är medlem i
     *
     * @var list<PersonExpandedAllOfEmbeddedGroupMemberships>
     */
    protected $groupMemberships;
    /**
     * De barn/barnen vilka personen har ansvar för.
     *
     * @return list<PersonResponsiblesInner>
     */
    public function getResponsibleFor(): array
    {
        return $this->responsibleFor;
    }
    /**
     * De barn/barnen vilka personen har ansvar för.
     *
     * @param list<PersonResponsiblesInner> $responsibleFor
     *
     * @return self
     */
    public function setResponsibleFor(array $responsibleFor): self
    {
        $this->initialized['responsibleFor'] = true;
        $this->responsibleFor = $responsibleFor;
        return $this;
    }
    /**
     * En lista med placeringar för personen.
     *
     * @return list<Placement>
     */
    public function getPlacements(): array
    {
        return $this->placements;
    }
    /**
     * En lista med placeringar för personen.
     *
     * @param list<Placement> $placements
     *
     * @return self
     */
    public function setPlacements(array $placements): self
    {
        $this->initialized['placements'] = true;
        $this->placements = $placements;
        return $this;
    }
    /**
     * En lista med placeringar där personen är satt som ägare.
     *
     * @return list<Placement>
     */
    public function getOwnedPlacements(): array
    {
        return $this->ownedPlacements;
    }
    /**
     * En lista med placeringar där personen är satt som ägare.
     *
     * @param list<Placement> $ownedPlacements
     *
     * @return self
     */
    public function setOwnedPlacements(array $ownedPlacements): self
    {
        $this->initialized['ownedPlacements'] = true;
        $this->ownedPlacements = $ownedPlacements;
        return $this;
    }
    /**
     * Personens aktuella tjänstgöring
     *
     * @return list<Duty>
     */
    public function getDuties(): array
    {
        return $this->duties;
    }
    /**
     * Personens aktuella tjänstgöring
     *
     * @param list<Duty> $duties
     *
     * @return self
     */
    public function setDuties(array $duties): self
    {
        $this->initialized['duties'] = true;
        $this->duties = $duties;
        return $this;
    }
    /**
     * En lista med grupper där personen är medlem i
     *
     * @return list<PersonExpandedAllOfEmbeddedGroupMemberships>
     */
    public function getGroupMemberships(): array
    {
        return $this->groupMemberships;
    }
    /**
     * En lista med grupper där personen är medlem i
     *
     * @param list<PersonExpandedAllOfEmbeddedGroupMemberships> $groupMemberships
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