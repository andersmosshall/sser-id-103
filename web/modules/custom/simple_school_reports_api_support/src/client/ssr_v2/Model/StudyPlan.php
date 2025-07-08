<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class StudyPlan extends \ArrayObject
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
     * @var mixed
     */
    protected $student;
    /**
     * 
     *
     * @var Meta
     */
    protected $meta;
    /**
     * 
     *
     * @var list<StudyPlanContent>
     */
    protected $content;
    /**
     * 
     *
     * @var list<StudyPlanNotes>
     */
    protected $notes;
    /**
     * Startdatum för studieplanen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för studieplanen  (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * @return mixed
     */
    public function getStudent()
    {
        return $this->student;
    }
    /**
     * 
     *
     * @param mixed $student
     *
     * @return self
     */
    public function setStudent($student): self
    {
        $this->initialized['student'] = true;
        $this->student = $student;
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
     * @return list<StudyPlanContent>
     */
    public function getContent(): array
    {
        return $this->content;
    }
    /**
     * 
     *
     * @param list<StudyPlanContent> $content
     *
     * @return self
     */
    public function setContent(array $content): self
    {
        $this->initialized['content'] = true;
        $this->content = $content;
        return $this;
    }
    /**
     * 
     *
     * @return list<StudyPlanNotes>
     */
    public function getNotes(): array
    {
        return $this->notes;
    }
    /**
     * 
     *
     * @param list<StudyPlanNotes> $notes
     *
     * @return self
     */
    public function setNotes(array $notes): self
    {
        $this->initialized['notes'] = true;
        $this->notes = $notes;
        return $this;
    }
    /**
     * Startdatum för studieplanen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för studieplanen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Slutdatum för studieplanen  (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för studieplanen  (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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