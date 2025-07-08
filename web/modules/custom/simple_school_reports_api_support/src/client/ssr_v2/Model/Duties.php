<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Duties extends \ArrayObject
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
     * @var list<DutyExpanded>
     */
    protected $data;
    /**
     * Om värdet är null finns inget mer att hämta på det token som skickades in som query parameter.
     *
     * @var string|null
     */
    protected $pageToken;
    /**
     * 
     *
     * @return list<DutyExpanded>
     */
    public function getData(): array
    {
        return $this->data;
    }
    /**
     * 
     *
     * @param list<DutyExpanded> $data
     *
     * @return self
     */
    public function setData(array $data): self
    {
        $this->initialized['data'] = true;
        $this->data = $data;
        return $this;
    }
    /**
     * Om värdet är null finns inget mer att hämta på det token som skickades in som query parameter.
     *
     * @return string|null
     */
    public function getPageToken(): ?string
    {
        return $this->pageToken;
    }
    /**
     * Om värdet är null finns inget mer att hämta på det token som skickades in som query parameter.
     *
     * @param string|null $pageToken
     *
     * @return self
     */
    public function setPageToken(?string $pageToken): self
    {
        $this->initialized['pageToken'] = true;
        $this->pageToken = $pageToken;
        return $this;
    }
}