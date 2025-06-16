<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PlacementsLookupPostRequest extends \ArrayObject
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
     * 
     *
     * @var list<string>
     */
    protected $personIds;
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
     * 
     *
     * @return list<string>
     */
    public function getPersonIds(): array
    {
        return $this->personIds;
    }
    /**
     * 
     *
     * @param list<string> $personIds
     *
     * @return self
     */
    public function setPersonIds(array $personIds): self
    {
        $this->initialized['personIds'] = true;
        $this->personIds = $personIds;
        return $this;
    }
}