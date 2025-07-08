<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Error extends \ArrayObject
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
     * Teknisk kod för att beskriva fel, varje källa bestämmer själv över möjliga felkoder.
     *
     * @var string
     */
    protected $code;
    /**
     * Text för att beskriva felet.
     *
     * @var string
     */
    protected $message;
    /**
     * Teknisk kod för att beskriva fel, varje källa bestämmer själv över möjliga felkoder.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }
    /**
     * Teknisk kod för att beskriva fel, varje källa bestämmer själv över möjliga felkoder.
     *
     * @param string $code
     *
     * @return self
     */
    public function setCode(string $code): self
    {
        $this->initialized['code'] = true;
        $this->code = $code;
        return $this;
    }
    /**
     * Text för att beskriva felet.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
    /**
     * Text för att beskriva felet.
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
}