<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PersonExpandedAllOf extends \ArrayObject
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
     * @var PersonExpandedAllOfEmbedded
     */
    protected $embedded;
    /**
     * 
     *
     * @return PersonExpandedAllOfEmbedded
     */
    public function getEmbedded(): PersonExpandedAllOfEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param PersonExpandedAllOfEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(PersonExpandedAllOfEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}