<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PlacementExpandedAllOf extends \ArrayObject
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
     * @var PlacementExpandedAllOfEmbedded
     */
    protected $embedded;
    /**
     * 
     *
     * @return PlacementExpandedAllOfEmbedded
     */
    public function getEmbedded(): PlacementExpandedAllOfEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param PlacementExpandedAllOfEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(PlacementExpandedAllOfEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}