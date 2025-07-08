<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Grade extends \ArrayObject
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
     * ID för betyget.
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
     * 
     *
     * @var mixed
     */
    protected $student;
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
    protected $registeredBy;
    /**
     * 
     *
     * @var mixed
     */
    protected $gradingTeacher;
    /**
     * 
     *
     * @var mixed
     */
    protected $group;
    /**
     * Det datum då betyget registrerades (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @var \DateTime
     */
    protected $registeredDate;
    /**
     * Anger vilket betyg eleven har tilldelats.
     *
     * @var string
     */
    protected $gradeValue;
    /**
     * Anger om det registrerade betyget är ett slutbetyg för ämnet eller kursen.
     *
     * @var bool
     */
    protected $finalGrade;
    /**
     * Anger om betyget satts vid en prövning. Förvalt värde är "false".
     *
     * @var bool
     */
    protected $trial = false;
    /**
     * Om en specialinriktad ämnesplan (GY) eller anpassad studiegång (GR) har använts för kursen så beskrivs här på vilket sätt studiegången anpassats.
     *
     * @var string
     */
    protected $adaptedStudyPlan;
    /**
     * Andra anmärkningar för betygsraden.
     *
     * @var string
     */
    protected $remark;
    /**
     * Anger om betyget är omvandlat. Förvalt värde är "false".
     *
     * @var bool
     */
    protected $converted = false;
    /**
     * Ändringstyp för betyget, om det är ändrat.
     *
     * @var string
     */
    protected $correctionType;
    /**
     * Om betyget avser höst- eller vårtermin.
     *
     * @var string
     */
    protected $semester;
    /**
     * Året som betyget gäller, exempelvis 2019.
     *
     * @var int
     */
    protected $year;
    /**
     * 
     *
     * @var SyllabusReference
     */
    protected $syllabus;
    /**
     * 
     *
     * @var GradeDiplomaProject
     */
    protected $diplomaProject;
    /**
     * ID för betyget.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * ID för betyget.
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
     * 
     *
     * @return mixed
     */
    public function getStudent()
    {
        return $this->student;
    }
    /**
     * 
     *
     * @param mixed $student
     *
     * @return self
     */
    public function setStudent($student): self
    {
        $this->initialized['student'] = true;
        $this->student = $student;
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
    public function getRegisteredBy()
    {
        return $this->registeredBy;
    }
    /**
     * 
     *
     * @param mixed $registeredBy
     *
     * @return self
     */
    public function setRegisteredBy($registeredBy): self
    {
        $this->initialized['registeredBy'] = true;
        $this->registeredBy = $registeredBy;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getGradingTeacher()
    {
        return $this->gradingTeacher;
    }
    /**
     * 
     *
     * @param mixed $gradingTeacher
     *
     * @return self
     */
    public function setGradingTeacher($gradingTeacher): self
    {
        $this->initialized['gradingTeacher'] = true;
        $this->gradingTeacher = $gradingTeacher;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }
    /**
     * 
     *
     * @param mixed $group
     *
     * @return self
     */
    public function setGroup($group): self
    {
        $this->initialized['group'] = true;
        $this->group = $group;
        return $this;
    }
    /**
     * Det datum då betyget registrerades (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @return \DateTime
     */
    public function getRegisteredDate(): \DateTime
    {
        return $this->registeredDate;
    }
    /**
     * Det datum då betyget registrerades (RFC 3339-format, t.ex. "2016-10-15").
     *
     * @param \DateTime $registeredDate
     *
     * @return self
     */
    public function setRegisteredDate(\DateTime $registeredDate): self
    {
        $this->initialized['registeredDate'] = true;
        $this->registeredDate = $registeredDate;
        return $this;
    }
    /**
     * Anger vilket betyg eleven har tilldelats.
     *
     * @return string
     */
    public function getGradeValue(): string
    {
        return $this->gradeValue;
    }
    /**
     * Anger vilket betyg eleven har tilldelats.
     *
     * @param string $gradeValue
     *
     * @return self
     */
    public function setGradeValue(string $gradeValue): self
    {
        $this->initialized['gradeValue'] = true;
        $this->gradeValue = $gradeValue;
        return $this;
    }
    /**
     * Anger om det registrerade betyget är ett slutbetyg för ämnet eller kursen.
     *
     * @return bool
     */
    public function getFinalGrade(): bool
    {
        return $this->finalGrade;
    }
    /**
     * Anger om det registrerade betyget är ett slutbetyg för ämnet eller kursen.
     *
     * @param bool $finalGrade
     *
     * @return self
     */
    public function setFinalGrade(bool $finalGrade): self
    {
        $this->initialized['finalGrade'] = true;
        $this->finalGrade = $finalGrade;
        return $this;
    }
    /**
     * Anger om betyget satts vid en prövning. Förvalt värde är "false".
     *
     * @return bool
     */
    public function getTrial(): bool
    {
        return $this->trial;
    }
    /**
     * Anger om betyget satts vid en prövning. Förvalt värde är "false".
     *
     * @param bool $trial
     *
     * @return self
     */
    public function setTrial(bool $trial): self
    {
        $this->initialized['trial'] = true;
        $this->trial = $trial;
        return $this;
    }
    /**
     * Om en specialinriktad ämnesplan (GY) eller anpassad studiegång (GR) har använts för kursen så beskrivs här på vilket sätt studiegången anpassats.
     *
     * @return string
     */
    public function getAdaptedStudyPlan(): string
    {
        return $this->adaptedStudyPlan;
    }
    /**
     * Om en specialinriktad ämnesplan (GY) eller anpassad studiegång (GR) har använts för kursen så beskrivs här på vilket sätt studiegången anpassats.
     *
     * @param string $adaptedStudyPlan
     *
     * @return self
     */
    public function setAdaptedStudyPlan(string $adaptedStudyPlan): self
    {
        $this->initialized['adaptedStudyPlan'] = true;
        $this->adaptedStudyPlan = $adaptedStudyPlan;
        return $this;
    }
    /**
     * Andra anmärkningar för betygsraden.
     *
     * @return string
     */
    public function getRemark(): string
    {
        return $this->remark;
    }
    /**
     * Andra anmärkningar för betygsraden.
     *
     * @param string $remark
     *
     * @return self
     */
    public function setRemark(string $remark): self
    {
        $this->initialized['remark'] = true;
        $this->remark = $remark;
        return $this;
    }
    /**
     * Anger om betyget är omvandlat. Förvalt värde är "false".
     *
     * @return bool
     */
    public function getConverted(): bool
    {
        return $this->converted;
    }
    /**
     * Anger om betyget är omvandlat. Förvalt värde är "false".
     *
     * @param bool $converted
     *
     * @return self
     */
    public function setConverted(bool $converted): self
    {
        $this->initialized['converted'] = true;
        $this->converted = $converted;
        return $this;
    }
    /**
     * Ändringstyp för betyget, om det är ändrat.
     *
     * @return string
     */
    public function getCorrectionType(): string
    {
        return $this->correctionType;
    }
    /**
     * Ändringstyp för betyget, om det är ändrat.
     *
     * @param string $correctionType
     *
     * @return self
     */
    public function setCorrectionType(string $correctionType): self
    {
        $this->initialized['correctionType'] = true;
        $this->correctionType = $correctionType;
        return $this;
    }
    /**
     * Om betyget avser höst- eller vårtermin.
     *
     * @return string
     */
    public function getSemester(): string
    {
        return $this->semester;
    }
    /**
     * Om betyget avser höst- eller vårtermin.
     *
     * @param string $semester
     *
     * @return self
     */
    public function setSemester(string $semester): self
    {
        $this->initialized['semester'] = true;
        $this->semester = $semester;
        return $this;
    }
    /**
     * Året som betyget gäller, exempelvis 2019.
     *
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }
    /**
     * Året som betyget gäller, exempelvis 2019.
     *
     * @param int $year
     *
     * @return self
     */
    public function setYear(int $year): self
    {
        $this->initialized['year'] = true;
        $this->year = $year;
        return $this;
    }
    /**
     * 
     *
     * @return SyllabusReference
     */
    public function getSyllabus(): SyllabusReference
    {
        return $this->syllabus;
    }
    /**
     * 
     *
     * @param SyllabusReference $syllabus
     *
     * @return self
     */
    public function setSyllabus(SyllabusReference $syllabus): self
    {
        $this->initialized['syllabus'] = true;
        $this->syllabus = $syllabus;
        return $this;
    }
    /**
     * 
     *
     * @return GradeDiplomaProject
     */
    public function getDiplomaProject(): GradeDiplomaProject
    {
        return $this->diplomaProject;
    }
    /**
     * 
     *
     * @param GradeDiplomaProject $diplomaProject
     *
     * @return self
     */
    public function setDiplomaProject(GradeDiplomaProject $diplomaProject): self
    {
        $this->initialized['diplomaProject'] = true;
        $this->diplomaProject = $diplomaProject;
        return $this;
    }
}