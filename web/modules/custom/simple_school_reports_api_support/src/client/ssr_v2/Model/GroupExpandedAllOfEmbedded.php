<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class GroupExpandedAllOfEmbedded extends \ArrayObject
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
     * @var list<GroupExpandedAllOfEmbeddedAssignmentRoles>
     */
    protected $assignmentRoles;
    /**
     * 
     *
     * @return list<GroupExpandedAllOfEmbeddedAssignmentRoles>
     */
    public function getAssignmentRoles(): array
    {
        return $this->assignmentRoles;
    }
    /**
     * 
     *
     * @param list<GroupExpandedAllOfEmbeddedAssignmentRoles> $assignmentRoles
     *
     * @return self
     */
    public function setAssignmentRoles(array $assignmentRoles): self
    {
        $this->initialized['assignmentRoles'] = true;
        $this->assignmentRoles = $assignmentRoles;
        return $this;
    }
}