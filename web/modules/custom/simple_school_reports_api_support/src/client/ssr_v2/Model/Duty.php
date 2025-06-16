<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Duty extends \ArrayObject
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
     * Ett ID för tjänsten.
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
     * 
     *
     * @var mixed
     */
    protected $person;
    /**
     * Datatyp som ska användas för att beskriva arbetsuppgifter för en person i relation till elevgrupp. Lärares undervisning ska inte uttryckas som en arbetsuppgift, för detta syfte används i stället Aktivitet.
     *
     * @var list<DutyAssignmentRoleInner>
     */
    protected $assignmentRole;
    /**
     * 
     *
     * @var OrganisationReference
     */
    protected $dutyAt;
    /**
     * 
     *
     * @var string
     */
    protected $dutyRole;
    /**
     * Arbetsområde. Kompletterande information till personalkategori, exempelvis Bibliotekarie.
     *
     * @var string
     */
    protected $description;
    /**
     * En signatur för tjänstgöringen exempelvis NJN, JOAN.
     *
     * @var string
     */
    protected $signature;
    /**
     * Tjänstgöringsgrad i procent
     *
     * @var int
     */
    protected $dutyPercent;
    /**
     * Antalet timmar tjänstgöringen omfattar under ett år.
     *
     * @var int
     */
    protected $hoursPerYear;
    /**
     * Startdatum för personens anställning på en viss skolenhet eller skola (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för personens anställning på en viss skolenhet eller skola (RFC 3339-format, e.g. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * Ett ID för tjänsten.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Ett ID för tjänsten.
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
     * 
     *
     * @return mixed
     */
    public function getPerson()
    {
        return $this->person;
    }
    /**
     * 
     *
     * @param mixed $person
     *
     * @return self
     */
    public function setPerson($person): self
    {
        $this->initialized['person'] = true;
        $this->person = $person;
        return $this;
    }
    /**
     * Datatyp som ska användas för att beskriva arbetsuppgifter för en person i relation till elevgrupp. Lärares undervisning ska inte uttryckas som en arbetsuppgift, för detta syfte används i stället Aktivitet.
     *
     * @return list<DutyAssignmentRoleInner>
     */
    public function getAssignmentRole(): array
    {
        return $this->assignmentRole;
    }
    /**
     * Datatyp som ska användas för att beskriva arbetsuppgifter för en person i relation till elevgrupp. Lärares undervisning ska inte uttryckas som en arbetsuppgift, för detta syfte används i stället Aktivitet.
     *
     * @param list<DutyAssignmentRoleInner> $assignmentRole
     *
     * @return self
     */
    public function setAssignmentRole(array $assignmentRole): self
    {
        $this->initialized['assignmentRole'] = true;
        $this->assignmentRole = $assignmentRole;
        return $this;
    }
    /**
     * 
     *
     * @return OrganisationReference
     */
    public function getDutyAt(): OrganisationReference
    {
        return $this->dutyAt;
    }
    /**
     * 
     *
     * @param OrganisationReference $dutyAt
     *
     * @return self
     */
    public function setDutyAt(OrganisationReference $dutyAt): self
    {
        $this->initialized['dutyAt'] = true;
        $this->dutyAt = $dutyAt;
        return $this;
    }
    /**
     * 
     *
     * @return string
     */
    public function getDutyRole(): string
    {
        return $this->dutyRole;
    }
    /**
     * 
     *
     * @param string $dutyRole
     *
     * @return self
     */
    public function setDutyRole(string $dutyRole): self
    {
        $this->initialized['dutyRole'] = true;
        $this->dutyRole = $dutyRole;
        return $this;
    }
    /**
     * Arbetsområde. Kompletterande information till personalkategori, exempelvis Bibliotekarie.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    /**
     * Arbetsområde. Kompletterande information till personalkategori, exempelvis Bibliotekarie.
     *
     * @param string $description
     *
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->initialized['description'] = true;
        $this->description = $description;
        return $this;
    }
    /**
     * En signatur för tjänstgöringen exempelvis NJN, JOAN.
     *
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }
    /**
     * En signatur för tjänstgöringen exempelvis NJN, JOAN.
     *
     * @param string $signature
     *
     * @return self
     */
    public function setSignature(string $signature): self
    {
        $this->initialized['signature'] = true;
        $this->signature = $signature;
        return $this;
    }
    /**
     * Tjänstgöringsgrad i procent
     *
     * @return int
     */
    public function getDutyPercent(): int
    {
        return $this->dutyPercent;
    }
    /**
     * Tjänstgöringsgrad i procent
     *
     * @param int $dutyPercent
     *
     * @return self
     */
    public function setDutyPercent(int $dutyPercent): self
    {
        $this->initialized['dutyPercent'] = true;
        $this->dutyPercent = $dutyPercent;
        return $this;
    }
    /**
     * Antalet timmar tjänstgöringen omfattar under ett år.
     *
     * @return int
     */
    public function getHoursPerYear(): int
    {
        return $this->hoursPerYear;
    }
    /**
     * Antalet timmar tjänstgöringen omfattar under ett år.
     *
     * @param int $hoursPerYear
     *
     * @return self
     */
    public function setHoursPerYear(int $hoursPerYear): self
    {
        $this->initialized['hoursPerYear'] = true;
        $this->hoursPerYear = $hoursPerYear;
        return $this;
    }
    /**
     * Startdatum för personens anställning på en viss skolenhet eller skola (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för personens anställning på en viss skolenhet eller skola (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Slutdatum för personens anställning på en viss skolenhet eller skola (RFC 3339-format, e.g. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för personens anställning på en viss skolenhet eller skola (RFC 3339-format, e.g. "2016-10-15"). Inkluderande.
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