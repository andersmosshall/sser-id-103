<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class ProgrammeContentInner extends \ArrayObject
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
     * Anger ingående kursers relation till programmet, såsom Programgemensamma. Typen _Inriktning_ kan endast anges för program av typen _Programinriktning_.
     *
     * @var string
     */
    protected $type;
    /**
     * Poäng för innehållstypen i förekommande fall.
     *
     * @var int
     */
    protected $points;
    /**
     * 
     *
     * @var list<mixed>
     */
    protected $content;
    /**
     * Anger ingående kursers relation till programmet, såsom Programgemensamma. Typen _Inriktning_ kan endast anges för program av typen _Programinriktning_.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    /**
     * Anger ingående kursers relation till programmet, såsom Programgemensamma. Typen _Inriktning_ kan endast anges för program av typen _Programinriktning_.
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
     * Poäng för innehållstypen i förekommande fall.
     *
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
    }
    /**
     * Poäng för innehållstypen i förekommande fall.
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
     * @return list<mixed>
     */
    public function getContent(): array
    {
        return $this->content;
    }
    /**
     * 
     *
     * @param list<mixed> $content
     *
     * @return self
     */
    public function setContent(array $content): self
    {
        $this->initialized['content'] = true;
        $this->content = $content;
        return $this;
    }
}