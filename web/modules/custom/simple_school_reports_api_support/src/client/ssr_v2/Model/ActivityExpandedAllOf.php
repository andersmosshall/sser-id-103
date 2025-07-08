<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class ActivityExpandedAllOf extends \ArrayObject
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
     * @var ActivityExpandedAllOfEmbedded
     */
    protected $embedded;
    /**
     * 
     *
     * @return ActivityExpandedAllOfEmbedded
     */
    public function getEmbedded(): ActivityExpandedAllOfEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param ActivityExpandedAllOfEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(ActivityExpandedAllOfEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}