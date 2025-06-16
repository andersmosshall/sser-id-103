<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Meta extends \ArrayObject
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
     * Datum och tid för när entiteten skapades (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @var \DateTime
     */
    protected $created;
    /**
     * Datum och tid för när entiteten senast uppdaterades (RFC 3339 format tex "2015-12-12T10:30:00+01:00"). Tidpunkten avser den senaste tidpunkt när något av de attribut som direkt tillhör entiteten har ändrats. Attribut som kan tas fram med parametrarna expand eller expandReferenceNames räknas **inte** som ett attribut till entiteten, och ska således **inte** påverka detta värde.
     *
     * @var \DateTime
     */
    protected $modified;
    /**
     * Datum och tid för när entiteten skapades (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }
    /**
     * Datum och tid för när entiteten skapades (RFC 3339 format tex "2015-12-12T10:30:00+01:00").
     *
     * @param \DateTime $created
     *
     * @return self
     */
    public function setCreated(\DateTime $created): self
    {
        $this->initialized['created'] = true;
        $this->created = $created;
        return $this;
    }
    /**
     * Datum och tid för när entiteten senast uppdaterades (RFC 3339 format tex "2015-12-12T10:30:00+01:00"). Tidpunkten avser den senaste tidpunkt när något av de attribut som direkt tillhör entiteten har ändrats. Attribut som kan tas fram med parametrarna expand eller expandReferenceNames räknas **inte** som ett attribut till entiteten, och ska således **inte** påverka detta värde.
     *
     * @return \DateTime
     */
    public function getModified(): \DateTime
    {
        return $this->modified;
    }
    /**
     * Datum och tid för när entiteten senast uppdaterades (RFC 3339 format tex "2015-12-12T10:30:00+01:00"). Tidpunkten avser den senaste tidpunkt när något av de attribut som direkt tillhör entiteten har ändrats. Attribut som kan tas fram med parametrarna expand eller expandReferenceNames räknas **inte** som ett attribut till entiteten, och ska således **inte** påverka detta värde.
     *
     * @param \DateTime $modified
     *
     * @return self
     */
    public function setModified(\DateTime $modified): self
    {
        $this->initialized['modified'] = true;
        $this->modified = $modified;
        return $this;
    }
}