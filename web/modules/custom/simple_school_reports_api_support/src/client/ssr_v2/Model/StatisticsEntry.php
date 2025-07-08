<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class StatisticsEntry extends \ArrayObject
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
    protected $resourceType;
    /**
     * Antalet nya objekt skapade hos klienten.
     *
     * @var int
     */
    protected $newCount;
    /**
     * Antalet uppdaterade objekt hos klienten.
     *
     * @var int
     */
    protected $updatedCount;
    /**
     * Antalet raderade objekt hos klienten.
     *
     * @var int
     */
    protected $deletedCount;
    /**
     * Eventuell url för relaterad fråga som låg till grund för synkroniserade objekt.
     *
     * @var string
     */
    protected $resourceUrl;
    /**
     * Tidpunkt för loggad händelse.
     *
     * @var \DateTime
     */
    protected $timeOfOccurance;
    /**
     * 
     *
     * @return string
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }
    /**
     * 
     *
     * @param string $resourceType
     *
     * @return self
     */
    public function setResourceType(string $resourceType): self
    {
        $this->initialized['resourceType'] = true;
        $this->resourceType = $resourceType;
        return $this;
    }
    /**
     * Antalet nya objekt skapade hos klienten.
     *
     * @return int
     */
    public function getNewCount(): int
    {
        return $this->newCount;
    }
    /**
     * Antalet nya objekt skapade hos klienten.
     *
     * @param int $newCount
     *
     * @return self
     */
    public function setNewCount(int $newCount): self
    {
        $this->initialized['newCount'] = true;
        $this->newCount = $newCount;
        return $this;
    }
    /**
     * Antalet uppdaterade objekt hos klienten.
     *
     * @return int
     */
    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }
    /**
     * Antalet uppdaterade objekt hos klienten.
     *
     * @param int $updatedCount
     *
     * @return self
     */
    public function setUpdatedCount(int $updatedCount): self
    {
        $this->initialized['updatedCount'] = true;
        $this->updatedCount = $updatedCount;
        return $this;
    }
    /**
     * Antalet raderade objekt hos klienten.
     *
     * @return int
     */
    public function getDeletedCount(): int
    {
        return $this->deletedCount;
    }
    /**
     * Antalet raderade objekt hos klienten.
     *
     * @param int $deletedCount
     *
     * @return self
     */
    public function setDeletedCount(int $deletedCount): self
    {
        $this->initialized['deletedCount'] = true;
        $this->deletedCount = $deletedCount;
        return $this;
    }
    /**
     * Eventuell url för relaterad fråga som låg till grund för synkroniserade objekt.
     *
     * @return string
     */
    public function getResourceUrl(): string
    {
        return $this->resourceUrl;
    }
    /**
     * Eventuell url för relaterad fråga som låg till grund för synkroniserade objekt.
     *
     * @param string $resourceUrl
     *
     * @return self
     */
    public function setResourceUrl(string $resourceUrl): self
    {
        $this->initialized['resourceUrl'] = true;
        $this->resourceUrl = $resourceUrl;
        return $this;
    }
    /**
     * Tidpunkt för loggad händelse.
     *
     * @return \DateTime
     */
    public function getTimeOfOccurance(): \DateTime
    {
        return $this->timeOfOccurance;
    }
    /**
     * Tidpunkt för loggad händelse.
     *
     * @param \DateTime $timeOfOccurance
     *
     * @return self
     */
    public function setTimeOfOccurance(\DateTime $timeOfOccurance): self
    {
        $this->initialized['timeOfOccurance'] = true;
        $this->timeOfOccurance = $timeOfOccurance;
        return $this;
    }
}