<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Subscription extends \ArrayObject
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
     * Ett beskrivande namn på webhook:en.
     *
     * @var string
     */
    protected $name;
    /**
     * URL:en som webhook:en ska posta till.
     *
     * @var string
     */
    protected $target;
    /**
     * 
     *
     * @var list<CreateSubscriptionResourceTypesInner>
     */
    protected $resourceTypes;
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
    /**
     * Ett beskrivande namn på webhook:en.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * Ett beskrivande namn på webhook:en.
     *
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->initialized['name'] = true;
        $this->name = $name;
        return $this;
    }
    /**
     * URL:en som webhook:en ska posta till.
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }
    /**
     * URL:en som webhook:en ska posta till.
     *
     * @param string $target
     *
     * @return self
     */
    public function setTarget(string $target): self
    {
        $this->initialized['target'] = true;
        $this->target = $target;
        return $this;
    }
    /**
     * 
     *
     * @return list<CreateSubscriptionResourceTypesInner>
     */
    public function getResourceTypes(): array
    {
        return $this->resourceTypes;
    }
    /**
     * 
     *
     * @param list<CreateSubscriptionResourceTypesInner> $resourceTypes
     *
     * @return self
     */
    public function setResourceTypes(array $resourceTypes): self
    {
        $this->initialized['resourceTypes'] = true;
        $this->resourceTypes = $resourceTypes;
        return $this;
    }
}