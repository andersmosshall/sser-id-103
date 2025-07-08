<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class ActivityExpanded extends \ArrayObject
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
     * Identifierare för aktiviteten.
     *
     * @var string
     */
    protected $id;
    /**
     * 
     *
     * @var Meta
     */
    protected $meta;
    /**
     * Namn på aktiviteten, i presentationssyfte.
     *
     * @var string
     */
    protected $displayName;
    /**
     * Detta ska uttrycka huruvida aktiviteten ska vara underlag för generering av lektion eller inte.
     *
     * @var bool
     */
    protected $calendarEventsRequired;
    /**
     * Datum för när aktiviteten startar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Datum för när aktiviteten slutar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
    * Beskriver vilken typ av aktivitet som avses.
    * _Undervisning_ - Schemalagd tid med koppling till timplan, som ska närvarorapporteras.
    * _Elevaktivitet_ - Schemalagd tid för elever som inte är undervisning enligt timplan, och som ska närvarorapporteras, exempelvis mentorstid, klassråd eller friluftsdag
    * _Provaktivitet_ - En aktivitet som är avsedd för att definiera ett eller flera provtillfällen
    * _Läraraktivitet_ - Tid för lärare som inte är undervisning och som ingår i lärarens arbetstid, kan vara schemalagd, men ska inte närvarorapporteras, exempelvis konferenstid.
    * _Övrigt_ - Läxhjälp, lunch, bokning och annat som finns på schemat, men inte är undervisning och inte ska närvarorapporteras
    
    *
    * @var string
    */
    protected $activityType;
    /**
     * En text med kompletterande information.
     *
     * @var string
     */
    protected $comment;
    /**
     * Den totalt planerade tiden i minuter.
     *
     * @var int
     */
    protected $minutesPlanned;
    /**
     * De grupper som är kopplade till aktiviteten.
     *
     * @var list<GroupReference>
     */
    protected $groups;
    /**
     * De lärare (Duty-objekt) som är kopplade till aktiviteten.
     *
     * @var list<DutyAssignment>
     */
    protected $teachers;
    /**
     * 
     *
     * @var mixed
     */
    protected $syllabus;
    /**
     * 
     *
     * @var mixed
     */
    protected $organisation;
    /**
     * 
     *
     * @var mixed
     */
    protected $parentActivity;
    /**
     * 
     *
     * @var ActivityExpandedAllOfEmbedded
     */
    protected $embedded;
    /**
     * Identifierare för aktiviteten.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Identifierare för aktiviteten.
     *
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): self
    {
        $this->initialized['id'] = true;
        $this->id = $id;
        return $this;
    }
    /**
     * 
     *
     * @return Meta
     */
    public function getMeta(): Meta
    {
        return $this->meta;
    }
    /**
     * 
     *
     * @param Meta $meta
     *
     * @return self
     */
    public function setMeta(Meta $meta): self
    {
        $this->initialized['meta'] = true;
        $this->meta = $meta;
        return $this;
    }
    /**
     * Namn på aktiviteten, i presentationssyfte.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    /**
     * Namn på aktiviteten, i presentationssyfte.
     *
     * @param string $displayName
     *
     * @return self
     */
    public function setDisplayName(string $displayName): self
    {
        $this->initialized['displayName'] = true;
        $this->displayName = $displayName;
        return $this;
    }
    /**
     * Detta ska uttrycka huruvida aktiviteten ska vara underlag för generering av lektion eller inte.
     *
     * @return bool
     */
    public function getCalendarEventsRequired(): bool
    {
        return $this->calendarEventsRequired;
    }
    /**
     * Detta ska uttrycka huruvida aktiviteten ska vara underlag för generering av lektion eller inte.
     *
     * @param bool $calendarEventsRequired
     *
     * @return self
     */
    public function setCalendarEventsRequired(bool $calendarEventsRequired): self
    {
        $this->initialized['calendarEventsRequired'] = true;
        $this->calendarEventsRequired = $calendarEventsRequired;
        return $this;
    }
    /**
     * Datum för när aktiviteten startar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Datum för när aktiviteten startar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @param \DateTime $startDate
     *
     * @return self
     */
    public function setStartDate(\DateTime $startDate): self
    {
        $this->initialized['startDate'] = true;
        $this->startDate = $startDate;
        return $this;
    }
    /**
     * Datum för när aktiviteten slutar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Datum för när aktiviteten slutar (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @param \DateTime $endDate
     *
     * @return self
     */
    public function setEndDate(\DateTime $endDate): self
    {
        $this->initialized['endDate'] = true;
        $this->endDate = $endDate;
        return $this;
    }
    /**
    * Beskriver vilken typ av aktivitet som avses.
    * _Undervisning_ - Schemalagd tid med koppling till timplan, som ska närvarorapporteras.
    * _Elevaktivitet_ - Schemalagd tid för elever som inte är undervisning enligt timplan, och som ska närvarorapporteras, exempelvis mentorstid, klassråd eller friluftsdag
    * _Provaktivitet_ - En aktivitet som är avsedd för att definiera ett eller flera provtillfällen
    * _Läraraktivitet_ - Tid för lärare som inte är undervisning och som ingår i lärarens arbetstid, kan vara schemalagd, men ska inte närvarorapporteras, exempelvis konferenstid.
    * _Övrigt_ - Läxhjälp, lunch, bokning och annat som finns på schemat, men inte är undervisning och inte ska närvarorapporteras
    
    *
    * @return string
    */
    public function getActivityType(): string
    {
        return $this->activityType;
    }
    /**
    * Beskriver vilken typ av aktivitet som avses.
    * _Undervisning_ - Schemalagd tid med koppling till timplan, som ska närvarorapporteras.
    * _Elevaktivitet_ - Schemalagd tid för elever som inte är undervisning enligt timplan, och som ska närvarorapporteras, exempelvis mentorstid, klassråd eller friluftsdag
    * _Provaktivitet_ - En aktivitet som är avsedd för att definiera ett eller flera provtillfällen
    * _Läraraktivitet_ - Tid för lärare som inte är undervisning och som ingår i lärarens arbetstid, kan vara schemalagd, men ska inte närvarorapporteras, exempelvis konferenstid.
    * _Övrigt_ - Läxhjälp, lunch, bokning och annat som finns på schemat, men inte är undervisning och inte ska närvarorapporteras
    
    *
    * @param string $activityType
    *
    * @return self
    */
    public function setActivityType(string $activityType): self
    {
        $this->initialized['activityType'] = true;
        $this->activityType = $activityType;
        return $this;
    }
    /**
     * En text med kompletterande information.
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
    /**
     * En text med kompletterande information.
     *
     * @param string $comment
     *
     * @return self
     */
    public function setComment(string $comment): self
    {
        $this->initialized['comment'] = true;
        $this->comment = $comment;
        return $this;
    }
    /**
     * Den totalt planerade tiden i minuter.
     *
     * @return int
     */
    public function getMinutesPlanned(): int
    {
        return $this->minutesPlanned;
    }
    /**
     * Den totalt planerade tiden i minuter.
     *
     * @param int $minutesPlanned
     *
     * @return self
     */
    public function setMinutesPlanned(int $minutesPlanned): self
    {
        $this->initialized['minutesPlanned'] = true;
        $this->minutesPlanned = $minutesPlanned;
        return $this;
    }
    /**
     * De grupper som är kopplade till aktiviteten.
     *
     * @return list<GroupReference>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
    /**
     * De grupper som är kopplade till aktiviteten.
     *
     * @param list<GroupReference> $groups
     *
     * @return self
     */
    public function setGroups(array $groups): self
    {
        $this->initialized['groups'] = true;
        $this->groups = $groups;
        return $this;
    }
    /**
     * De lärare (Duty-objekt) som är kopplade till aktiviteten.
     *
     * @return list<DutyAssignment>
     */
    public function getTeachers(): array
    {
        return $this->teachers;
    }
    /**
     * De lärare (Duty-objekt) som är kopplade till aktiviteten.
     *
     * @param list<DutyAssignment> $teachers
     *
     * @return self
     */
    public function setTeachers(array $teachers): self
    {
        $this->initialized['teachers'] = true;
        $this->teachers = $teachers;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getSyllabus()
    {
        return $this->syllabus;
    }
    /**
     * 
     *
     * @param mixed $syllabus
     *
     * @return self
     */
    public function setSyllabus($syllabus): self
    {
        $this->initialized['syllabus'] = true;
        $this->syllabus = $syllabus;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getOrganisation()
    {
        return $this->organisation;
    }
    /**
     * 
     *
     * @param mixed $organisation
     *
     * @return self
     */
    public function setOrganisation($organisation): self
    {
        $this->initialized['organisation'] = true;
        $this->organisation = $organisation;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getParentActivity()
    {
        return $this->parentActivity;
    }
    /**
     * 
     *
     * @param mixed $parentActivity
     *
     * @return self
     */
    public function setParentActivity($parentActivity): self
    {
        $this->initialized['parentActivity'] = true;
        $this->parentActivity = $parentActivity;
        return $this;
    }
    /**
     * 
     *
     * @return ActivityExpandedAllOfEmbedded
     */
    public function getEmbedded(): ActivityExpandedAllOfEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param ActivityExpandedAllOfEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(ActivityExpandedAllOfEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}