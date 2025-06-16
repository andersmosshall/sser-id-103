<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class SchoolUnitOffering extends \ArrayObject
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
     * Startdatum för när uppsättningen av program/kurser erbjuds vid skolan. (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för när uppsättningen av program/kurser erbjuds vid skolan. Ett angivet datum innebär att utbudet inte längre är giltigt efter angivet datum. (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * 
     *
     * @var mixed
     */
    protected $offeredAt;
    /**
     * 
     *
     * @var list<mixed>
     */
    protected $offeredSyllabuses;
    /**
     * 
     *
     * @var list<ProgrammeReference>
     */
    protected $offeredProgrammes;
    /**
     * Startdatum för när uppsättningen av program/kurser erbjuds vid skolan. (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för när uppsättningen av program/kurser erbjuds vid skolan. (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Slutdatum för när uppsättningen av program/kurser erbjuds vid skolan. Ett angivet datum innebär att utbudet inte längre är giltigt efter angivet datum. (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för när uppsättningen av program/kurser erbjuds vid skolan. Ett angivet datum innebär att utbudet inte längre är giltigt efter angivet datum. (RFC 3339-format, t.ex. "2016-10-15").
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
    /**
     * 
     *
     * @return mixed
     */
    public function getOfferedAt()
    {
        return $this->offeredAt;
    }
    /**
     * 
     *
     * @param mixed $offeredAt
     *
     * @return self
     */
    public function setOfferedAt($offeredAt): self
    {
        $this->initialized['offeredAt'] = true;
        $this->offeredAt = $offeredAt;
        return $this;
    }
    /**
     * 
     *
     * @return list<mixed>
     */
    public function getOfferedSyllabuses(): array
    {
        return $this->offeredSyllabuses;
    }
    /**
     * 
     *
     * @param list<mixed> $offeredSyllabuses
     *
     * @return self
     */
    public function setOfferedSyllabuses(array $offeredSyllabuses): self
    {
        $this->initialized['offeredSyllabuses'] = true;
        $this->offeredSyllabuses = $offeredSyllabuses;
        return $this;
    }
    /**
     * 
     *
     * @return list<ProgrammeReference>
     */
    public function getOfferedProgrammes(): array
    {
        return $this->offeredProgrammes;
    }
    /**
     * 
     *
     * @param list<ProgrammeReference> $offeredProgrammes
     *
     * @return self
     */
    public function setOfferedProgrammes(array $offeredProgrammes): self
    {
        $this->initialized['offeredProgrammes'] = true;
        $this->offeredProgrammes = $offeredProgrammes;
        return $this;
    }
}