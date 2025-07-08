<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class DeletedEntitiesData extends \ArrayObject
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
    protected $absences;
    /**
     * 
     *
     * @var list<string>
     */
    protected $attendanceEvents;
    /**
     * 
     *
     * @var list<string>
     */
    protected $attendances;
    /**
     * 
     *
     * @var list<string>
     */
    protected $grades;
    /**
     * 
     *
     * @var list<string>
     */
    protected $calendarEvents;
    /**
     * 
     *
     * @var list<string>
     */
    protected $attendanceSchedules;
    /**
     * 
     *
     * @var list<string>
     */
    protected $resources;
    /**
     * 
     *
     * @var list<string>
     */
    protected $rooms;
    /**
     * 
     *
     * @var list<string>
     */
    protected $activitites;
    /**
     * 
     *
     * @var list<string>
     */
    protected $duties;
    /**
     * 
     *
     * @var list<string>
     */
    protected $placements;
    /**
     * 
     *
     * @var list<string>
     */
    protected $studyPlans;
    /**
     * 
     *
     * @var list<string>
     */
    protected $programmes;
    /**
     * 
     *
     * @var list<string>
     */
    protected $syllabuses;
    /**
     * 
     *
     * @var list<string>
     */
    protected $schoolUnitOfferings;
    /**
     * 
     *
     * @var list<string>
     */
    protected $groups;
    /**
     * 
     *
     * @var list<string>
     */
    protected $persons;
    /**
     * 
     *
     * @var list<string>
     */
    protected $organisations;
    /**
     * 
     *
     * @return list<string>
     */
    public function getAbsences(): array
    {
        return $this->absences;
    }
    /**
     * 
     *
     * @param list<string> $absences
     *
     * @return self
     */
    public function setAbsences(array $absences): self
    {
        $this->initialized['absences'] = true;
        $this->absences = $absences;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getAttendanceEvents(): array
    {
        return $this->attendanceEvents;
    }
    /**
     * 
     *
     * @param list<string> $attendanceEvents
     *
     * @return self
     */
    public function setAttendanceEvents(array $attendanceEvents): self
    {
        $this->initialized['attendanceEvents'] = true;
        $this->attendanceEvents = $attendanceEvents;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getAttendances(): array
    {
        return $this->attendances;
    }
    /**
     * 
     *
     * @param list<string> $attendances
     *
     * @return self
     */
    public function setAttendances(array $attendances): self
    {
        $this->initialized['attendances'] = true;
        $this->attendances = $attendances;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getGrades(): array
    {
        return $this->grades;
    }
    /**
     * 
     *
     * @param list<string> $grades
     *
     * @return self
     */
    public function setGrades(array $grades): self
    {
        $this->initialized['grades'] = true;
        $this->grades = $grades;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getCalendarEvents(): array
    {
        return $this->calendarEvents;
    }
    /**
     * 
     *
     * @param list<string> $calendarEvents
     *
     * @return self
     */
    public function setCalendarEvents(array $calendarEvents): self
    {
        $this->initialized['calendarEvents'] = true;
        $this->calendarEvents = $calendarEvents;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getAttendanceSchedules(): array
    {
        return $this->attendanceSchedules;
    }
    /**
     * 
     *
     * @param list<string> $attendanceSchedules
     *
     * @return self
     */
    public function setAttendanceSchedules(array $attendanceSchedules): self
    {
        $this->initialized['attendanceSchedules'] = true;
        $this->attendanceSchedules = $attendanceSchedules;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getResources(): array
    {
        return $this->resources;
    }
    /**
     * 
     *
     * @param list<string> $resources
     *
     * @return self
     */
    public function setResources(array $resources): self
    {
        $this->initialized['resources'] = true;
        $this->resources = $resources;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getRooms(): array
    {
        return $this->rooms;
    }
    /**
     * 
     *
     * @param list<string> $rooms
     *
     * @return self
     */
    public function setRooms(array $rooms): self
    {
        $this->initialized['rooms'] = true;
        $this->rooms = $rooms;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getActivitites(): array
    {
        return $this->activitites;
    }
    /**
     * 
     *
     * @param list<string> $activitites
     *
     * @return self
     */
    public function setActivitites(array $activitites): self
    {
        $this->initialized['activitites'] = true;
        $this->activitites = $activitites;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getDuties(): array
    {
        return $this->duties;
    }
    /**
     * 
     *
     * @param list<string> $duties
     *
     * @return self
     */
    public function setDuties(array $duties): self
    {
        $this->initialized['duties'] = true;
        $this->duties = $duties;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getPlacements(): array
    {
        return $this->placements;
    }
    /**
     * 
     *
     * @param list<string> $placements
     *
     * @return self
     */
    public function setPlacements(array $placements): self
    {
        $this->initialized['placements'] = true;
        $this->placements = $placements;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getStudyPlans(): array
    {
        return $this->studyPlans;
    }
    /**
     * 
     *
     * @param list<string> $studyPlans
     *
     * @return self
     */
    public function setStudyPlans(array $studyPlans): self
    {
        $this->initialized['studyPlans'] = true;
        $this->studyPlans = $studyPlans;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getProgrammes(): array
    {
        return $this->programmes;
    }
    /**
     * 
     *
     * @param list<string> $programmes
     *
     * @return self
     */
    public function setProgrammes(array $programmes): self
    {
        $this->initialized['programmes'] = true;
        $this->programmes = $programmes;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getSyllabuses(): array
    {
        return $this->syllabuses;
    }
    /**
     * 
     *
     * @param list<string> $syllabuses
     *
     * @return self
     */
    public function setSyllabuses(array $syllabuses): self
    {
        $this->initialized['syllabuses'] = true;
        $this->syllabuses = $syllabuses;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getSchoolUnitOfferings(): array
    {
        return $this->schoolUnitOfferings;
    }
    /**
     * 
     *
     * @param list<string> $schoolUnitOfferings
     *
     * @return self
     */
    public function setSchoolUnitOfferings(array $schoolUnitOfferings): self
    {
        $this->initialized['schoolUnitOfferings'] = true;
        $this->schoolUnitOfferings = $schoolUnitOfferings;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }
    /**
     * 
     *
     * @param list<string> $groups
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
     * 
     *
     * @return list<string>
     */
    public function getPersons(): array
    {
        return $this->persons;
    }
    /**
     * 
     *
     * @param list<string> $persons
     *
     * @return self
     */
    public function setPersons(array $persons): self
    {
        $this->initialized['persons'] = true;
        $this->persons = $persons;
        return $this;
    }
    /**
     * 
     *
     * @return list<string>
     */
    public function getOrganisations(): array
    {
        return $this->organisations;
    }
    /**
     * 
     *
     * @param list<string> $organisations
     *
     * @return self
     */
    public function setOrganisations(array $organisations): self
    {
        $this->initialized['organisations'] = true;
        $this->organisations = $organisations;
        return $this;
    }
}