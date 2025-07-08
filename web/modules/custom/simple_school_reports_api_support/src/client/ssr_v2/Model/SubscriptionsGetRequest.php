<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class SubscriptionsGetRequest extends \ArrayObject
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
     * En lista med datatyper där det finns uppdaterad eller ny information att hämta på respektive ändpunkt.
     *
     * @var list<string>
     */
    protected $modifiedEntites;
    /**
     * True indikerar att det finns information om borttagna entiteter att hämta från ändpunkten `deletedEntitites`.
     *
     * @var bool
     */
    protected $deletedEntities;
    /**
     * En lista med datatyper där det finns uppdaterad eller ny information att hämta på respektive ändpunkt.
     *
     * @return list<string>
     */
    public function getModifiedEntites(): array
    {
        return $this->modifiedEntites;
    }
    /**
     * En lista med datatyper där det finns uppdaterad eller ny information att hämta på respektive ändpunkt.
     *
     * @param list<string> $modifiedEntites
     *
     * @return self
     */
    public function setModifiedEntites(array $modifiedEntites): self
    {
        $this->initialized['modifiedEntites'] = true;
        $this->modifiedEntites = $modifiedEntites;
        return $this;
    }
    /**
     * True indikerar att det finns information om borttagna entiteter att hämta från ändpunkten `deletedEntitites`.
     *
     * @return bool
     */
    public function getDeletedEntities(): bool
    {
        return $this->deletedEntities;
    }
    /**
     * True indikerar att det finns information om borttagna entiteter att hämta från ändpunkten `deletedEntitites`.
     *
     * @param bool $deletedEntities
     *
     * @return self
     */
    public function setDeletedEntities(bool $deletedEntities): self
    {
        $this->initialized['deletedEntities'] = true;
        $this->deletedEntities = $deletedEntities;
        return $this;
    }
}