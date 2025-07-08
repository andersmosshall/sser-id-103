<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class GradeDiplomaProject extends \ArrayObject
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
     * Titel på gymnasiearbete.
     *
     * @var string
     */
    protected $title;
    /**
     * Beskrivning av gymnasiearbete.
     *
     * @var string
     */
    protected $description;
    /**
     * Eventuell engelsk titel på gymnasiearbete.
     *
     * @var string
     */
    protected $titleEnglish;
    /**
     * Eventuell engelsk beskrivning av gymnasiearbete.
     *
     * @var string
     */
    protected $descriptionEnglish;
    /**
     * Titel på gymnasiearbete.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    /**
     * Titel på gymnasiearbete.
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
     * Beskrivning av gymnasiearbete.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    /**
     * Beskrivning av gymnasiearbete.
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
     * Eventuell engelsk titel på gymnasiearbete.
     *
     * @return string
     */
    public function getTitleEnglish(): string
    {
        return $this->titleEnglish;
    }
    /**
     * Eventuell engelsk titel på gymnasiearbete.
     *
     * @param string $titleEnglish
     *
     * @return self
     */
    public function setTitleEnglish(string $titleEnglish): self
    {
        $this->initialized['titleEnglish'] = true;
        $this->titleEnglish = $titleEnglish;
        return $this;
    }
    /**
     * Eventuell engelsk beskrivning av gymnasiearbete.
     *
     * @return string
     */
    public function getDescriptionEnglish(): string
    {
        return $this->descriptionEnglish;
    }
    /**
     * Eventuell engelsk beskrivning av gymnasiearbete.
     *
     * @param string $descriptionEnglish
     *
     * @return self
     */
    public function setDescriptionEnglish(string $descriptionEnglish): self
    {
        $this->initialized['descriptionEnglish'] = true;
        $this->descriptionEnglish = $descriptionEnglish;
        return $this;
    }
}