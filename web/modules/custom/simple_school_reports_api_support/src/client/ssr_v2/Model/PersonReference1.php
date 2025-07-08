<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PersonReference1 extends \ArrayObject
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
     * Återspeglar värdet från folkbokföringsregistret.
     *
     * @var string
     */
    protected $securityMarking;
    /**
     * Återspeglar värdet från folkbokföringsregistret.
     *
     * @return string
     */
    public function getSecurityMarking(): string
    {
        return $this->securityMarking;
    }
    /**
     * Återspeglar värdet från folkbokföringsregistret.
     *
     * @param string $securityMarking
     *
     * @return self
     */
    public function setSecurityMarking(string $securityMarking): self
    {
        $this->initialized['securityMarking'] = true;
        $this->securityMarking = $securityMarking;
        return $this;
    }
}