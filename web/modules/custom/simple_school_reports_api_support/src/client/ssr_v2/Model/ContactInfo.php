<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class ContactInfo extends \ArrayObject
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
     * Typ av kontaktuppgift. Publika uppgifter avser uppgifter som kan visas för allmänheten, såsom adress och telefonnummer till skolan.
     *
     * @var string
     */
    protected $infoType;
    /**
     * Beskrivning i fritext av kontaktuppgifter till skolan eller organisationsenheten.
     *
     * @var string
     */
    protected $info;
    /**
     * Typ av kontaktuppgift. Publika uppgifter avser uppgifter som kan visas för allmänheten, såsom adress och telefonnummer till skolan.
     *
     * @return string
     */
    public function getInfoType(): string
    {
        return $this->infoType;
    }
    /**
     * Typ av kontaktuppgift. Publika uppgifter avser uppgifter som kan visas för allmänheten, såsom adress och telefonnummer till skolan.
     *
     * @param string $infoType
     *
     * @return self
     */
    public function setInfoType(string $infoType): self
    {
        $this->initialized['infoType'] = true;
        $this->infoType = $infoType;
        return $this;
    }
    /**
     * Beskrivning i fritext av kontaktuppgifter till skolan eller organisationsenheten.
     *
     * @return string
     */
    public function getInfo(): string
    {
        return $this->info;
    }
    /**
     * Beskrivning i fritext av kontaktuppgifter till skolan eller organisationsenheten.
     *
     * @param string $info
     *
     * @return self
     */
    public function setInfo(string $info): self
    {
        $this->initialized['info'] = true;
        $this->info = $info;
        return $this;
    }
}