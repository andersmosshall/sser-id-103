<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class DutyExpandedAllOfEmbedded extends \ArrayObject
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
    protected $person;
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
}