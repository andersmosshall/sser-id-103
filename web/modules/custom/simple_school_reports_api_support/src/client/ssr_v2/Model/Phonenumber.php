<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Phonenumber extends \ArrayObject
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
     * Telefonnumret.
     *
     * @var string
     */
    protected $value;
    /**
     * 
     *
     * @var string
     */
    protected $type;
    /**
     * 
     *
     * @var bool
     */
    protected $mobile = true;
    /**
     * Telefonnumret.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
    /**
     * Telefonnumret.
     *
     * @param string $value
     *
     * @return self
     */
    public function setValue(string $value): self
    {
        $this->initialized['value'] = true;
        $this->value = $value;
        return $this;
    }
    /**
     * 
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    /**
     * 
     *
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->initialized['type'] = true;
        $this->type = $type;
        return $this;
    }
    /**
     * 
     *
     * @return bool
     */
    public function getMobile(): bool
    {
        return $this->mobile;
    }
    /**
     * 
     *
     * @param bool $mobile
     *
     * @return self
     */
    public function setMobile(bool $mobile): self
    {
        $this->initialized['mobile'] = true;
        $this->mobile = $mobile;
        return $this;
    }
}