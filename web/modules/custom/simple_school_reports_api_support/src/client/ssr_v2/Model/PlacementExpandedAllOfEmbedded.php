<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PlacementExpandedAllOfEmbedded extends \ArrayObject
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
    protected $child;
    /**
     * 
     *
     * @var list<Person>
     */
    protected $owners;
    /**
     * 
     *
     * @return Person
     */
    public function getChild(): Person
    {
        return $this->child;
    }
    /**
     * 
     *
     * @param Person $child
     *
     * @return self
     */
    public function setChild(Person $child): self
    {
        $this->initialized['child'] = true;
        $this->child = $child;
        return $this;
    }
    /**
     * 
     *
     * @return list<Person>
     */
    public function getOwners(): array
    {
        return $this->owners;
    }
    /**
     * 
     *
     * @param list<Person> $owners
     *
     * @return self
     */
    public function setOwners(array $owners): self
    {
        $this->initialized['owners'] = true;
        $this->owners = $owners;
        return $this;
    }
}