<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Person extends \ArrayObject
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
     * Ett objekt ska ha samma överförings-ID mellan samtliga ingående system och således är det ett enda namespace för de gemensamma ID:na. Objektidentifikatorn är den nyckel som skall vara persistent mellan olika processer (enl figur 1).
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
     * Förnamn.
     *
     * @var string
     */
    protected $givenName;
    /**
     * Mellannamn.
     *
     * @var string
     */
    protected $middleName;
    /**
     * Efternamn.
     *
     * @var string
     */
    protected $familyName;
    /**
     * De identifierare som ska användas för att identifiera användaren i skilda e-tjänster. Identifieraren ska vara en spårbar, persistent och globalt unik sträng. Den ska bestå av en lokalt unik användaridentifierare, ett ’@’ och en domän. En domän är ofta, men inte nödvändigtvis, samma som organisationens internet-domännamn. _Exempel: kalko@edu.goteborg.se_
     *
     * @var list<string>
     */
    protected $eduPersonPrincipalNames;
    /**
     * 
     *
     * @var list<ExternalIdentifier>
     */
    protected $externalIdentifiers;
    /**
     * Personnummer.
     *
     * @var PersonCivicNo
     */
    protected $civicNo;
    /**
     * Födelsedatum (RFC 3339-format, t.ex. "2016-10-15")
     *
     * @var \DateTime
     */
    protected $birthDate;
    /**
     * Biologiskt kön
     *
     * @var string
     */
    protected $sex;
    /**
     * Återspeglar värdet från folkbokföringsregistret.
     *
     * @var string
     */
    protected $securityMarking;
    /**
     * Anger ifall en person har en aktiv status eller en annan status, såsom utvandrad eller avliden.
     *
     * @var string
     */
    protected $personStatus = 'Aktiv';
    /**
     * En lista med personens epostadresser
     *
     * @var list<Email>
     */
    protected $emails;
    /**
     * En lista med telefonnummer till personen.
     *
     * @var list<Phonenumber>
     */
    protected $phoneNumbers;
    /**
     * En lista med personens postadresser
     *
     * @var list<PersonAddressesInner>
     */
    protected $addresses;
    /**
     * Pekar ut en resurs med en bild på personen, specificeras som en URI enligt RFC 3986.
     *
     * @var string
     */
    protected $photo;
    /**
     * En lista med inskrivningar för personen
     *
     * @var list<Enrolment>
     */
    protected $enrolments;
    /**
     * Personens vårdnadshavare eller motsvarande relationer så som familjehemsförälder. Denna relation beskriver ett officiellt ansvarsförhållande.
     *
     * @var list<PersonResponsiblesInner>
     */
    protected $responsibles;
    /**
     * Ett objekt ska ha samma överförings-ID mellan samtliga ingående system och således är det ett enda namespace för de gemensamma ID:na. Objektidentifikatorn är den nyckel som skall vara persistent mellan olika processer (enl figur 1).
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Ett objekt ska ha samma överförings-ID mellan samtliga ingående system och således är det ett enda namespace för de gemensamma ID:na. Objektidentifikatorn är den nyckel som skall vara persistent mellan olika processer (enl figur 1).
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
     * Förnamn.
     *
     * @return string
     */
    public function getGivenName(): string
    {
        return $this->givenName;
    }
    /**
     * Förnamn.
     *
     * @param string $givenName
     *
     * @return self
     */
    public function setGivenName(string $givenName): self
    {
        $this->initialized['givenName'] = true;
        $this->givenName = $givenName;
        return $this;
    }
    /**
     * Mellannamn.
     *
     * @return string
     */
    public function getMiddleName(): string
    {
        return $this->middleName;
    }
    /**
     * Mellannamn.
     *
     * @param string $middleName
     *
     * @return self
     */
    public function setMiddleName(string $middleName): self
    {
        $this->initialized['middleName'] = true;
        $this->middleName = $middleName;
        return $this;
    }
    /**
     * Efternamn.
     *
     * @return string
     */
    public function getFamilyName(): string
    {
        return $this->familyName;
    }
    /**
     * Efternamn.
     *
     * @param string $familyName
     *
     * @return self
     */
    public function setFamilyName(string $familyName): self
    {
        $this->initialized['familyName'] = true;
        $this->familyName = $familyName;
        return $this;
    }
    /**
     * De identifierare som ska användas för att identifiera användaren i skilda e-tjänster. Identifieraren ska vara en spårbar, persistent och globalt unik sträng. Den ska bestå av en lokalt unik användaridentifierare, ett ’@’ och en domän. En domän är ofta, men inte nödvändigtvis, samma som organisationens internet-domännamn. _Exempel: kalko@edu.goteborg.se_
     *
     * @return list<string>
     */
    public function getEduPersonPrincipalNames(): array
    {
        return $this->eduPersonPrincipalNames;
    }
    /**
     * De identifierare som ska användas för att identifiera användaren i skilda e-tjänster. Identifieraren ska vara en spårbar, persistent och globalt unik sträng. Den ska bestå av en lokalt unik användaridentifierare, ett ’@’ och en domän. En domän är ofta, men inte nödvändigtvis, samma som organisationens internet-domännamn. _Exempel: kalko@edu.goteborg.se_
     *
     * @param list<string> $eduPersonPrincipalNames
     *
     * @return self
     */
    public function setEduPersonPrincipalNames(array $eduPersonPrincipalNames): self
    {
        $this->initialized['eduPersonPrincipalNames'] = true;
        $this->eduPersonPrincipalNames = $eduPersonPrincipalNames;
        return $this;
    }
    /**
     * 
     *
     * @return list<ExternalIdentifier>
     */
    public function getExternalIdentifiers(): array
    {
        return $this->externalIdentifiers;
    }
    /**
     * 
     *
     * @param list<ExternalIdentifier> $externalIdentifiers
     *
     * @return self
     */
    public function setExternalIdentifiers(array $externalIdentifiers): self
    {
        $this->initialized['externalIdentifiers'] = true;
        $this->externalIdentifiers = $externalIdentifiers;
        return $this;
    }
    /**
     * Personnummer.
     *
     * @return PersonCivicNo
     */
    public function getCivicNo(): PersonCivicNo
    {
        return $this->civicNo;
    }
    /**
     * Personnummer.
     *
     * @param PersonCivicNo $civicNo
     *
     * @return self
     */
    public function setCivicNo(PersonCivicNo $civicNo): self
    {
        $this->initialized['civicNo'] = true;
        $this->civicNo = $civicNo;
        return $this;
    }
    /**
     * Födelsedatum (RFC 3339-format, t.ex. "2016-10-15")
     *
     * @return \DateTime
     */
    public function getBirthDate(): \DateTime
    {
        return $this->birthDate;
    }
    /**
     * Födelsedatum (RFC 3339-format, t.ex. "2016-10-15")
     *
     * @param \DateTime $birthDate
     *
     * @return self
     */
    public function setBirthDate(\DateTime $birthDate): self
    {
        $this->initialized['birthDate'] = true;
        $this->birthDate = $birthDate;
        return $this;
    }
    /**
     * Biologiskt kön
     *
     * @return string
     */
    public function getSex(): string
    {
        return $this->sex;
    }
    /**
     * Biologiskt kön
     *
     * @param string $sex
     *
     * @return self
     */
    public function setSex(string $sex): self
    {
        $this->initialized['sex'] = true;
        $this->sex = $sex;
        return $this;
    }
    /**
     * Återspeglar värdet från folkbokföringsregistret.
     *
     * @return string
     */
    public function getSecurityMarking(): string
    {
        return $this->securityMarking;
    }
    /**
     * Återspeglar värdet från folkbokföringsregistret.
     *
     * @param string $securityMarking
     *
     * @return self
     */
    public function setSecurityMarking(string $securityMarking): self
    {
        $this->initialized['securityMarking'] = true;
        $this->securityMarking = $securityMarking;
        return $this;
    }
    /**
     * Anger ifall en person har en aktiv status eller en annan status, såsom utvandrad eller avliden.
     *
     * @return string
     */
    public function getPersonStatus(): string
    {
        return $this->personStatus;
    }
    /**
     * Anger ifall en person har en aktiv status eller en annan status, såsom utvandrad eller avliden.
     *
     * @param string $personStatus
     *
     * @return self
     */
    public function setPersonStatus(string $personStatus): self
    {
        $this->initialized['personStatus'] = true;
        $this->personStatus = $personStatus;
        return $this;
    }
    /**
     * En lista med personens epostadresser
     *
     * @return list<Email>
     */
    public function getEmails(): array
    {
        return $this->emails;
    }
    /**
     * En lista med personens epostadresser
     *
     * @param list<Email> $emails
     *
     * @return self
     */
    public function setEmails(array $emails): self
    {
        $this->initialized['emails'] = true;
        $this->emails = $emails;
        return $this;
    }
    /**
     * En lista med telefonnummer till personen.
     *
     * @return list<Phonenumber>
     */
    public function getPhoneNumbers(): array
    {
        return $this->phoneNumbers;
    }
    /**
     * En lista med telefonnummer till personen.
     *
     * @param list<Phonenumber> $phoneNumbers
     *
     * @return self
     */
    public function setPhoneNumbers(array $phoneNumbers): self
    {
        $this->initialized['phoneNumbers'] = true;
        $this->phoneNumbers = $phoneNumbers;
        return $this;
    }
    /**
     * En lista med personens postadresser
     *
     * @return list<PersonAddressesInner>
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }
    /**
     * En lista med personens postadresser
     *
     * @param list<PersonAddressesInner> $addresses
     *
     * @return self
     */
    public function setAddresses(array $addresses): self
    {
        $this->initialized['addresses'] = true;
        $this->addresses = $addresses;
        return $this;
    }
    /**
     * Pekar ut en resurs med en bild på personen, specificeras som en URI enligt RFC 3986.
     *
     * @return string
     */
    public function getPhoto(): string
    {
        return $this->photo;
    }
    /**
     * Pekar ut en resurs med en bild på personen, specificeras som en URI enligt RFC 3986.
     *
     * @param string $photo
     *
     * @return self
     */
    public function setPhoto(string $photo): self
    {
        $this->initialized['photo'] = true;
        $this->photo = $photo;
        return $this;
    }
    /**
     * En lista med inskrivningar för personen
     *
     * @return list<Enrolment>
     */
    public function getEnrolments(): array
    {
        return $this->enrolments;
    }
    /**
     * En lista med inskrivningar för personen
     *
     * @param list<Enrolment> $enrolments
     *
     * @return self
     */
    public function setEnrolments(array $enrolments): self
    {
        $this->initialized['enrolments'] = true;
        $this->enrolments = $enrolments;
        return $this;
    }
    /**
     * Personens vårdnadshavare eller motsvarande relationer så som familjehemsförälder. Denna relation beskriver ett officiellt ansvarsförhållande.
     *
     * @return list<PersonResponsiblesInner>
     */
    public function getResponsibles(): array
    {
        return $this->responsibles;
    }
    /**
     * Personens vårdnadshavare eller motsvarande relationer så som familjehemsförälder. Denna relation beskriver ett officiellt ansvarsförhållande.
     *
     * @param list<PersonResponsiblesInner> $responsibles
     *
     * @return self
     */
    public function setResponsibles(array $responsibles): self
    {
        $this->initialized['responsibles'] = true;
        $this->responsibles = $responsibles;
        return $this;
    }
}