<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PlacementReference extends \ArrayObject
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
     * Namn för visningsyfte för det refererade objektet. Skall endast returneras när query parametern `expandReferenceNames` är satt till "true".
     *
     * @var string
     */
    protected $displayName;
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
     * Namn för visningsyfte för det refererade objektet. Skall endast returneras när query parametern `expandReferenceNames` är satt till "true".
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    /**
     * Namn för visningsyfte för det refererade objektet. Skall endast returneras när query parametern `expandReferenceNames` är satt till "true".
     *
     * @param string $displayName
     *
     * @return self
     */
    public function setDisplayName(string $displayName): self
    {
        $this->initialized['displayName'] = true;
        $this->displayName = $displayName;
        return $this;
    }
}