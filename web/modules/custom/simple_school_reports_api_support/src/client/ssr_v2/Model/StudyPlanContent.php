<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class StudyPlanContent extends \ArrayObject
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
     * Anger rubriken i elevens studieplan
     *
     * @var string
     */
    protected $title;
    /**
     * 
     *
     * @var string
     */
    protected $type;
    /**
     * Anger poängtalet för den aktuella kategorin av kurser
     *
     * @var int
     */
    protected $points;
    /**
     * 
     *
     * @var list<StudyPlanSyllabus>
     */
    protected $syllabuses;
    /**
     * Anger rubriken i elevens studieplan
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    /**
     * Anger rubriken i elevens studieplan
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->initialized['title'] = true;
        $this->title = $title;
        return $this;
    }
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
     * Anger poängtalet för den aktuella kategorin av kurser
     *
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
    }
    /**
     * Anger poängtalet för den aktuella kategorin av kurser
     *
     * @param int $points
     *
     * @return self
     */
    public function setPoints(int $points): self
    {
        $this->initialized['points'] = true;
        $this->points = $points;
        return $this;
    }
    /**
     * 
     *
     * @return list<StudyPlanSyllabus>
     */
    public function getSyllabuses(): array
    {
        return $this->syllabuses;
    }
    /**
     * 
     *
     * @param list<StudyPlanSyllabus> $syllabuses
     *
     * @return self
     */
    public function setSyllabuses(array $syllabuses): self
    {
        $this->initialized['syllabuses'] = true;
        $this->syllabuses = $syllabuses;
        return $this;
    }
}