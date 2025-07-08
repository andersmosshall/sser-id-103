<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PersonResponsiblesInner extends \ArrayObject
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
     * @var PersonReference
     */
    protected $person;
    /**
     * Värdeförråd som skall användas för olika typer av relationer till ett barn eller en elev.
     *
     * @var string
     */
    protected $relationType;
    /**
     * 
     *
     * @return PersonReference
     */
    public function getPerson(): PersonReference
    {
        return $this->person;
    }
    /**
     * 
     *
     * @param PersonReference $person
     *
     * @return self
     */
    public function setPerson(PersonReference $person): self
    {
        $this->initialized['person'] = true;
        $this->person = $person;
        return $this;
    }
    /**
     * Värdeförråd som skall användas för olika typer av relationer till ett barn eller en elev.
     *
     * @return string
     */
    public function getRelationType(): string
    {
        return $this->relationType;
    }
    /**
     * Värdeförråd som skall användas för olika typer av relationer till ett barn eller en elev.
     *
     * @param string $relationType
     *
     * @return self
     */
    public function setRelationType(string $relationType): self
    {
        $this->initialized['relationType'] = true;
        $this->relationType = $relationType;
        return $this;
    }
}