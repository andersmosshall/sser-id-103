<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class GroupExpandedAllOf extends \ArrayObject
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
     * @var GroupExpandedAllOfEmbedded
     */
    protected $embedded;
    /**
     * 
     *
     * @return GroupExpandedAllOfEmbedded
     */
    public function getEmbedded(): GroupExpandedAllOfEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param GroupExpandedAllOfEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(GroupExpandedAllOfEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}