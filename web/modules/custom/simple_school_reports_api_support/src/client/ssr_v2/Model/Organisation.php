<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Organisation extends \ArrayObject
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
     * @var Meta
     */
    protected $meta;
    /**
     * Namn på organisationen
     *
     * @var string
     */
    protected $displayName;
    /**
     * En kod för att identifiera organisationselementet inom organisationen.
     *
     * @var string
     */
    protected $organisationCode;
    /**
     * Typ av organisation. Notera att Stadsdel är deprekerad och kommer tas bort i nästa version. Rimlig synonym är Förvaltning.
     *
     * @var string
     */
    protected $organisationType;
    /**
     * Identitetsbeteckning för juridiska personer såsom kommun eller bolag
     *
     * @var string
     */
    protected $organisationNumber;
    /**
     * 
     *
     * @var mixed
     */
    protected $parentOrganisation;
    /**
     * Skolenhetskod. Identifierare för skolenheten enligt Skolverket. Används för de skolformer där skolverket bestämmer en skolenhetskod för varje enhet.
     *
     * @var string
     */
    protected $schoolUnitCode;
    /**
     * Anges endas för organisationselement typen Skolenhet.
     *
     * @var list<string>
     */
    protected $schoolTypes;
    /**
     * Organisationens postadress
     *
     * @var OrganisationAddress
     */
    protected $address;
    /**
     * Kommunkod. Län och kommunkod för den kommun där skolan är belägen, exempelvis 0180 där 01 anger länet och 80 anger kommunen.
     *
     * @var string
     */
    protected $municipalityCode;
    /**
     * Länk till en websida med information om skolan eller organisationselementet.
     *
     * @var string
     */
    protected $url;
    /**
     * Epost-adress till skolan eller organisationselementet.
     *
     * @var string
     */
    protected $email;
    /**
     * Telefonnummer till en skolan eller organisationselementet.
     *
     * @var string
     */
    protected $phoneNumber;
    /**
     * 
     *
     * @var list<ContactInfo>
     */
    protected $contactInfo;
    /**
     * Startdatum för organisationensdelens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Gäller för alla underliggande element som inte har ett mer restrektivt värde. Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för organisationensdelens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Gäller för alla underliggande element som inte har ett mer restrektivt värde. Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
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
     * @return Meta
     */
    public function getMeta(): Meta
    {
        return $this->meta;
    }
    /**
     * 
     *
     * @param Meta $meta
     *
     * @return self
     */
    public function setMeta(Meta $meta): self
    {
        $this->initialized['meta'] = true;
        $this->meta = $meta;
        return $this;
    }
    /**
     * Namn på organisationen
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    /**
     * Namn på organisationen
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
    /**
     * En kod för att identifiera organisationselementet inom organisationen.
     *
     * @return string
     */
    public function getOrganisationCode(): string
    {
        return $this->organisationCode;
    }
    /**
     * En kod för att identifiera organisationselementet inom organisationen.
     *
     * @param string $organisationCode
     *
     * @return self
     */
    public function setOrganisationCode(string $organisationCode): self
    {
        $this->initialized['organisationCode'] = true;
        $this->organisationCode = $organisationCode;
        return $this;
    }
    /**
     * Typ av organisation. Notera att Stadsdel är deprekerad och kommer tas bort i nästa version. Rimlig synonym är Förvaltning.
     *
     * @return string
     */
    public function getOrganisationType(): string
    {
        return $this->organisationType;
    }
    /**
     * Typ av organisation. Notera att Stadsdel är deprekerad och kommer tas bort i nästa version. Rimlig synonym är Förvaltning.
     *
     * @param string $organisationType
     *
     * @return self
     */
    public function setOrganisationType(string $organisationType): self
    {
        $this->initialized['organisationType'] = true;
        $this->organisationType = $organisationType;
        return $this;
    }
    /**
     * Identitetsbeteckning för juridiska personer såsom kommun eller bolag
     *
     * @return string
     */
    public function getOrganisationNumber(): string
    {
        return $this->organisationNumber;
    }
    /**
     * Identitetsbeteckning för juridiska personer såsom kommun eller bolag
     *
     * @param string $organisationNumber
     *
     * @return self
     */
    public function setOrganisationNumber(string $organisationNumber): self
    {
        $this->initialized['organisationNumber'] = true;
        $this->organisationNumber = $organisationNumber;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getParentOrganisation()
    {
        return $this->parentOrganisation;
    }
    /**
     * 
     *
     * @param mixed $parentOrganisation
     *
     * @return self
     */
    public function setParentOrganisation($parentOrganisation): self
    {
        $this->initialized['parentOrganisation'] = true;
        $this->parentOrganisation = $parentOrganisation;
        return $this;
    }
    /**
     * Skolenhetskod. Identifierare för skolenheten enligt Skolverket. Används för de skolformer där skolverket bestämmer en skolenhetskod för varje enhet.
     *
     * @return string
     */
    public function getSchoolUnitCode(): string
    {
        return $this->schoolUnitCode;
    }
    /**
     * Skolenhetskod. Identifierare för skolenheten enligt Skolverket. Används för de skolformer där skolverket bestämmer en skolenhetskod för varje enhet.
     *
     * @param string $schoolUnitCode
     *
     * @return self
     */
    public function setSchoolUnitCode(string $schoolUnitCode): self
    {
        $this->initialized['schoolUnitCode'] = true;
        $this->schoolUnitCode = $schoolUnitCode;
        return $this;
    }
    /**
     * Anges endas för organisationselement typen Skolenhet.
     *
     * @return list<string>
     */
    public function getSchoolTypes(): array
    {
        return $this->schoolTypes;
    }
    /**
     * Anges endas för organisationselement typen Skolenhet.
     *
     * @param list<string> $schoolTypes
     *
     * @return self
     */
    public function setSchoolTypes(array $schoolTypes): self
    {
        $this->initialized['schoolTypes'] = true;
        $this->schoolTypes = $schoolTypes;
        return $this;
    }
    /**
     * Organisationens postadress
     *
     * @return OrganisationAddress
     */
    public function getAddress(): OrganisationAddress
    {
        return $this->address;
    }
    /**
     * Organisationens postadress
     *
     * @param OrganisationAddress $address
     *
     * @return self
     */
    public function setAddress(OrganisationAddress $address): self
    {
        $this->initialized['address'] = true;
        $this->address = $address;
        return $this;
    }
    /**
     * Kommunkod. Län och kommunkod för den kommun där skolan är belägen, exempelvis 0180 där 01 anger länet och 80 anger kommunen.
     *
     * @return string
     */
    public function getMunicipalityCode(): string
    {
        return $this->municipalityCode;
    }
    /**
     * Kommunkod. Län och kommunkod för den kommun där skolan är belägen, exempelvis 0180 där 01 anger länet och 80 anger kommunen.
     *
     * @param string $municipalityCode
     *
     * @return self
     */
    public function setMunicipalityCode(string $municipalityCode): self
    {
        $this->initialized['municipalityCode'] = true;
        $this->municipalityCode = $municipalityCode;
        return $this;
    }
    /**
     * Länk till en websida med information om skolan eller organisationselementet.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
    /**
     * Länk till en websida med information om skolan eller organisationselementet.
     *
     * @param string $url
     *
     * @return self
     */
    public function setUrl(string $url): self
    {
        $this->initialized['url'] = true;
        $this->url = $url;
        return $this;
    }
    /**
     * Epost-adress till skolan eller organisationselementet.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
    /**
     * Epost-adress till skolan eller organisationselementet.
     *
     * @param string $email
     *
     * @return self
     */
    public function setEmail(string $email): self
    {
        $this->initialized['email'] = true;
        $this->email = $email;
        return $this;
    }
    /**
     * Telefonnummer till en skolan eller organisationselementet.
     *
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }
    /**
     * Telefonnummer till en skolan eller organisationselementet.
     *
     * @param string $phoneNumber
     *
     * @return self
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->initialized['phoneNumber'] = true;
        $this->phoneNumber = $phoneNumber;
        return $this;
    }
    /**
     * 
     *
     * @return list<ContactInfo>
     */
    public function getContactInfo(): array
    {
        return $this->contactInfo;
    }
    /**
     * 
     *
     * @param list<ContactInfo> $contactInfo
     *
     * @return self
     */
    public function setContactInfo(array $contactInfo): self
    {
        $this->initialized['contactInfo'] = true;
        $this->contactInfo = $contactInfo;
        return $this;
    }
    /**
     * Startdatum för organisationensdelens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Gäller för alla underliggande element som inte har ett mer restrektivt värde. Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för organisationensdelens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Gäller för alla underliggande element som inte har ett mer restrektivt värde. Inkluderande.
     *
     * @param \DateTime $startDate
     *
     * @return self
     */
    public function setStartDate(\DateTime $startDate): self
    {
        $this->initialized['startDate'] = true;
        $this->startDate = $startDate;
        return $this;
    }
    /**
     * Slutdatum för organisationensdelens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Gäller för alla underliggande element som inte har ett mer restrektivt värde. Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för organisationensdelens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Gäller för alla underliggande element som inte har ett mer restrektivt värde. Inkluderande.
     *
     * @param \DateTime $endDate
     *
     * @return self
     */
    public function setEndDate(\DateTime $endDate): self
    {
        $this->initialized['endDate'] = true;
        $this->endDate = $endDate;
        return $this;
    }
}