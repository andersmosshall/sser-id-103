<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PersonsLookupPostRequest extends \ArrayObject
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
    protected $civicNos;
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
    public function getCivicNos(): array
    {
        return $this->civicNos;
    }
    /**
     * 
     *
     * @param list<string> $civicNos
     *
     * @return self
     */
    public function setCivicNos(array $civicNos): self
    {
        $this->initialized['civicNos'] = true;
        $this->civicNos = $civicNos;
        return $this;
    }
}