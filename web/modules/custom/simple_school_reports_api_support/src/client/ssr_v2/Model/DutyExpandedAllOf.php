<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class DutyExpandedAllOf extends \ArrayObject
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
     * @var DutyExpandedAllOfEmbedded
     */
    protected $embedded;
    /**
     * 
     *
     * @return DutyExpandedAllOfEmbedded
     */
    public function getEmbedded(): DutyExpandedAllOfEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param DutyExpandedAllOfEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(DutyExpandedAllOfEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}