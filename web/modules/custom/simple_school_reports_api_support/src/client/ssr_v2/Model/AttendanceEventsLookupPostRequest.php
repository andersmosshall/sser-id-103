<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class AttendanceEventsLookupPostRequest extends \ArrayObject
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
     * @var list<string>
     */
    protected $ids;
    /**
     * 
     *
     * @var list<string>
     */
    protected $person;
    /**
     * 
     *
     * @var list<string>
     */
    protected $group;
    /**
     * 
     *
     * @return list<string>
     */
    public function getIds(): array
    {
        return $this->ids;
    }
    /**
     * 
     *
     * @param list<string> $ids
     *
     * @return self
     */
    public function setIds(array $ids): self
    {
        $this->initialized['ids'] = true;
        $this->ids = $ids;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getPerson(): array
    {
        return $this->person;
    }
    /**
     * 
     *
     * @param list<string> $person
     *
     * @return self
     */
    public function setPerson(array $person): self
    {
        $this->initialized['person'] = true;
        $this->person = $person;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getGroup(): array
    {
        return $this->group;
    }
    /**
     * 
     *
     * @param list<string> $group
     *
     * @return self
     */
    public function setGroup(array $group): self
    {
        $this->initialized['group'] = true;
        $this->group = $group;
        return $this;
    }
}