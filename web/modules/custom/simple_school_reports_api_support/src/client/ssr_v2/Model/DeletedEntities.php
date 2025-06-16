<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class DeletedEntities extends \ArrayObject
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
     * @var DeletedEntitiesData
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
     * @return DeletedEntitiesData
     */
    public function getData(): DeletedEntitiesData
    {
        return $this->data;
    }
    /**
     * 
     *
     * @param DeletedEntitiesData $data
     *
     * @return self
     */
    public function setData(DeletedEntitiesData $data): self
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