<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Room extends \ArrayObject
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
     * @var string
     */
    protected $id;
    /**
     * 
     *
     * @var Meta
     */
    protected $meta;
    /**
     * Namn på rum/lokal/plats.
     *
     * @var string
     */
    protected $displayName;
    /**
     * Antal platser i lokalen.
     *
     * @var int
     */
    protected $seats;
    /**
     * 
     *
     * @var OrganisationReference
     */
    protected $owner;
    /**
     * 
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * 
     *
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): self
    {
        $this->initialized['id'] = true;
        $this->id = $id;
        return $this;
    }
    /**
     * 
     *
     * @return Meta
     */
    public function getMeta(): Meta
    {
        return $this->meta;
    }
    /**
     * 
     *
     * @param Meta $meta
     *
     * @return self
     */
    public function setMeta(Meta $meta): self
    {
        $this->initialized['meta'] = true;
        $this->meta = $meta;
        return $this;
    }
    /**
     * Namn på rum/lokal/plats.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    /**
     * Namn på rum/lokal/plats.
     *
     * @param string $displayName
     *
     * @return self
     */
    public function setDisplayName(string $displayName): self
    {
        $this->initialized['displayName'] = true;
        $this->displayName = $displayName;
        return $this;
    }
    /**
     * Antal platser i lokalen.
     *
     * @return int
     */
    public function getSeats(): int
    {
        return $this->seats;
    }
    /**
     * Antal platser i lokalen.
     *
     * @param int $seats
     *
     * @return self
     */
    public function setSeats(int $seats): self
    {
        $this->initialized['seats'] = true;
        $this->seats = $seats;
        return $this;
    }
    /**
     * 
     *
     * @return OrganisationReference
     */
    public function getOwner(): OrganisationReference
    {
        return $this->owner;
    }
    /**
     * 
     *
     * @param OrganisationReference $owner
     *
     * @return self
     */
    public function setOwner(OrganisationReference $owner): self
    {
        $this->initialized['owner'] = true;
        $this->owner = $owner;
        return $this;
    }
}