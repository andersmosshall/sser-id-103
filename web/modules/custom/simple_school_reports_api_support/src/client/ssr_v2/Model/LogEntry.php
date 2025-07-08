<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class LogEntry extends \ArrayObject
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
     * Situationssepcifik text rörande loggad händelse.
     *
     * @var string
     */
    protected $message;
    /**
     * Hos klienten unikt ID på meddelandetypen
     *
     * @var string
     */
    protected $messageType;
    /**
     * 
     *
     * @var string
     */
    protected $resourceType;
    /**
     * Eventuellt id relaterat till loggad händelse.
     *
     * @var string
     */
    protected $resourceId;
    /**
     * Eventuell url till relaterad fråga som låg till grund till loggad händelse.
     *
     * @var string
     */
    protected $resourceUrl;
    /**
    * Loggad händelses allvarlighetsgrad.
    * _Info_ - Händelse som ej är ett problem men kan vara bra att veta vid felsökning.
    * _Warning_ - Behöver uppmärksammas, eventuellt problem.
    * _Error_ - Problem som kärver någon typ av åtgärd.
    
    *
    * @var string
    */
    protected $severityLevel;
    /**
     * Tidpunkt för loggad händelse.
     *
     * @var \DateTime
     */
    protected $timeOfOccurance;
    /**
     * Situationssepcifik text rörande loggad händelse.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    /**
     * Situationssepcifik text rörande loggad händelse.
     *
     * @param string $message
     *
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->initialized['message'] = true;
        $this->message = $message;
        return $this;
    }
    /**
     * Hos klienten unikt ID på meddelandetypen
     *
     * @return string
     */
    public function getMessageType(): string
    {
        return $this->messageType;
    }
    /**
     * Hos klienten unikt ID på meddelandetypen
     *
     * @param string $messageType
     *
     * @return self
     */
    public function setMessageType(string $messageType): self
    {
        $this->initialized['messageType'] = true;
        $this->messageType = $messageType;
        return $this;
    }
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
     * Eventuellt id relaterat till loggad händelse.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return $this->resourceId;
    }
    /**
     * Eventuellt id relaterat till loggad händelse.
     *
     * @param string $resourceId
     *
     * @return self
     */
    public function setResourceId(string $resourceId): self
    {
        $this->initialized['resourceId'] = true;
        $this->resourceId = $resourceId;
        return $this;
    }
    /**
     * Eventuell url till relaterad fråga som låg till grund till loggad händelse.
     *
     * @return string
     */
    public function getResourceUrl(): string
    {
        return $this->resourceUrl;
    }
    /**
     * Eventuell url till relaterad fråga som låg till grund till loggad händelse.
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
    * Loggad händelses allvarlighetsgrad.
    * _Info_ - Händelse som ej är ett problem men kan vara bra att veta vid felsökning.
    * _Warning_ - Behöver uppmärksammas, eventuellt problem.
    * _Error_ - Problem som kärver någon typ av åtgärd.
    
    *
    * @return string
    */
    public function getSeverityLevel(): string
    {
        return $this->severityLevel;
    }
    /**
    * Loggad händelses allvarlighetsgrad.
    * _Info_ - Händelse som ej är ett problem men kan vara bra att veta vid felsökning.
    * _Warning_ - Behöver uppmärksammas, eventuellt problem.
    * _Error_ - Problem som kärver någon typ av åtgärd.
    
    *
    * @param string $severityLevel
    *
    * @return self
    */
    public function setSeverityLevel(string $severityLevel): self
    {
        $this->initialized['severityLevel'] = true;
        $this->severityLevel = $severityLevel;
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