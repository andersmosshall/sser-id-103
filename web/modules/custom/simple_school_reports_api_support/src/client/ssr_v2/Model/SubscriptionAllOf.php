<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class SubscriptionAllOf extends \ArrayObject
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
     * @var string
     */
    protected $id;
    /**
     * 
     *
     * @var \DateTime
     */
    protected $expires;
    /**
     * 
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * 
     *
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): self
    {
        $this->initialized['id'] = true;
        $this->id = $id;
        return $this;
    }
    /**
     * 
     *
     * @return \DateTime
     */
    public function getExpires(): \DateTime
    {
        return $this->expires;
    }
    /**
     * 
     *
     * @param \DateTime $expires
     *
     * @return self
     */
    public function setExpires(\DateTime $expires): self
    {
        $this->initialized['expires'] = true;
        $this->expires = $expires;
        return $this;
    }
}