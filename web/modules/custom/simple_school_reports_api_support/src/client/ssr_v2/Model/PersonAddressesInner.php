<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PersonAddressesInner extends \ArrayObject
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
    protected $type = 'Folkbokföring';
    /**
     * Gatuadress.
     *
     * @var string
     */
    protected $streetAddress;
    /**
     * Postort.
     *
     * @var string
     */
    protected $locality;
    /**
     * Postadress.
     *
     * @var string
     */
    protected $postalCode;
    /**
     * Län, kod
     *
     * @var int
     */
    protected $countyCode;
    /**
     * Kommun, kod
     *
     * @var int
     */
    protected $municipalityCode;
    /**
     * Fastighetsbeteckning
     *
     * @var string
     */
    protected $realEstateDesignation;
    /**
     * Land.
     *
     * @var string
     */
    protected $country;
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
     * Gatuadress.
     *
     * @return string
     */
    public function getStreetAddress(): string
    {
        return $this->streetAddress;
    }
    /**
     * Gatuadress.
     *
     * @param string $streetAddress
     *
     * @return self
     */
    public function setStreetAddress(string $streetAddress): self
    {
        $this->initialized['streetAddress'] = true;
        $this->streetAddress = $streetAddress;
        return $this;
    }
    /**
     * Postort.
     *
     * @return string
     */
    public function getLocality(): string
    {
        return $this->locality;
    }
    /**
     * Postort.
     *
     * @param string $locality
     *
     * @return self
     */
    public function setLocality(string $locality): self
    {
        $this->initialized['locality'] = true;
        $this->locality = $locality;
        return $this;
    }
    /**
     * Postadress.
     *
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }
    /**
     * Postadress.
     *
     * @param string $postalCode
     *
     * @return self
     */
    public function setPostalCode(string $postalCode): self
    {
        $this->initialized['postalCode'] = true;
        $this->postalCode = $postalCode;
        return $this;
    }
    /**
     * Län, kod
     *
     * @return int
     */
    public function getCountyCode(): int
    {
        return $this->countyCode;
    }
    /**
     * Län, kod
     *
     * @param int $countyCode
     *
     * @return self
     */
    public function setCountyCode(int $countyCode): self
    {
        $this->initialized['countyCode'] = true;
        $this->countyCode = $countyCode;
        return $this;
    }
    /**
     * Kommun, kod
     *
     * @return int
     */
    public function getMunicipalityCode(): int
    {
        return $this->municipalityCode;
    }
    /**
     * Kommun, kod
     *
     * @param int $municipalityCode
     *
     * @return self
     */
    public function setMunicipalityCode(int $municipalityCode): self
    {
        $this->initialized['municipalityCode'] = true;
        $this->municipalityCode = $municipalityCode;
        return $this;
    }
    /**
     * Fastighetsbeteckning
     *
     * @return string
     */
    public function getRealEstateDesignation(): string
    {
        return $this->realEstateDesignation;
    }
    /**
     * Fastighetsbeteckning
     *
     * @param string $realEstateDesignation
     *
     * @return self
     */
    public function setRealEstateDesignation(string $realEstateDesignation): self
    {
        $this->initialized['realEstateDesignation'] = true;
        $this->realEstateDesignation = $realEstateDesignation;
        return $this;
    }
    /**
     * Land.
     *
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }
    /**
     * Land.
     *
     * @param string $country
     *
     * @return self
     */
    public function setCountry(string $country): self
    {
        $this->initialized['country'] = true;
        $this->country = $country;
        return $this;
    }
}