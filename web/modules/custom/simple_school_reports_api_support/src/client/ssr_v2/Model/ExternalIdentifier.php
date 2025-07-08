<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class ExternalIdentifier extends \ArrayObject
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
     * Identifierare för användaren.
     *
     * @var string
     */
    protected $value;
    /**
     * Anger för vilket sammanhang användaridentifieraren ska användas. Beskriv med en URI. Värdet kan överenskommas bilateralt mellan två integrerande parter.
     *
     * @var string
     */
    protected $context;
    /**
     * Anger om identifieraren är så utformad att den kan anses vara globalt unik.
     *
     * @var bool
     */
    protected $globallyUnique;
    /**
     * Identifierare för användaren.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
    /**
     * Identifierare för användaren.
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
     * Anger för vilket sammanhang användaridentifieraren ska användas. Beskriv med en URI. Värdet kan överenskommas bilateralt mellan två integrerande parter.
     *
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }
    /**
     * Anger för vilket sammanhang användaridentifieraren ska användas. Beskriv med en URI. Värdet kan överenskommas bilateralt mellan två integrerande parter.
     *
     * @param string $context
     *
     * @return self
     */
    public function setContext(string $context): self
    {
        $this->initialized['context'] = true;
        $this->context = $context;
        return $this;
    }
    /**
     * Anger om identifieraren är så utformad att den kan anses vara globalt unik.
     *
     * @return bool
     */
    public function getGloballyUnique(): bool
    {
        return $this->globallyUnique;
    }
    /**
     * Anger om identifieraren är så utformad att den kan anses vara globalt unik.
     *
     * @param bool $globallyUnique
     *
     * @return self
     */
    public function setGloballyUnique(bool $globallyUnique): self
    {
        $this->initialized['globallyUnique'] = true;
        $this->globallyUnique = $globallyUnique;
        return $this;
    }
}