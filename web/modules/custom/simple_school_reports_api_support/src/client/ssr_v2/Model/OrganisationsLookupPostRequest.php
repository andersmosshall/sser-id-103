<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class OrganisationsLookupPostRequest extends \ArrayObject
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
     * @var list<string>
     */
    protected $ids;
    /**
     * 
     *
     * @var list<string>
     */
    protected $schoolUnitCodes;
    /**
     * 
     *
     * @var list<string>
     */
    protected $organisationCodes;
    /**
     * 
     *
     * @return list<string>
     */
    public function getIds(): array
    {
        return $this->ids;
    }
    /**
     * 
     *
     * @param list<string> $ids
     *
     * @return self
     */
    public function setIds(array $ids): self
    {
        $this->initialized['ids'] = true;
        $this->ids = $ids;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getSchoolUnitCodes(): array
    {
        return $this->schoolUnitCodes;
    }
    /**
     * 
     *
     * @param list<string> $schoolUnitCodes
     *
     * @return self
     */
    public function setSchoolUnitCodes(array $schoolUnitCodes): self
    {
        $this->initialized['schoolUnitCodes'] = true;
        $this->schoolUnitCodes = $schoolUnitCodes;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getOrganisationCodes(): array
    {
        return $this->organisationCodes;
    }
    /**
     * 
     *
     * @param list<string> $organisationCodes
     *
     * @return self
     */
    public function setOrganisationCodes(array $organisationCodes): self
    {
        $this->initialized['organisationCodes'] = true;
        $this->organisationCodes = $organisationCodes;
        return $this;
    }
}