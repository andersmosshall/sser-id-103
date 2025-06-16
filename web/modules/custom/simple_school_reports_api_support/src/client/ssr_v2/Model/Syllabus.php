<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Syllabus extends \ArrayObject
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
    * Följande värden används för att beskriva skolform:
     - _FS_ - Förskola 
     - _FKLASS_ - Förskoleklass 
     - _FTH_ - Fritidshem
     - _OPPFTH_ - Öppen fritidsverksamhet 
     - _GR_ - Grundskola 
     - _GRS_ - Grundsärskola 
     - _TR_ - Träningsskolan
     - _SP_ - Specialskola 
     - _SAM_ - Sameskola 
     - _GY_ - Gymnasieskola 
     - _GYS_ - Gymnasiesärskola 
     - _VUX_ - Kommunal vuxenutbildning
     - _VUXSFI_ - Kommunal vuxenutbildning i svenska för invandrare
     - _VUXGR_ - Kommunal vuxenutbildning på grundläggande nivå
     - _VUXGY_ - Kommunal vuxenutbildning på gymnasial nivå
     - _VUXSARGR_ - Kommunal vuxenutbildning som särskild utbildning på grundläggande nivå
     - _VUXSARTR_ - Kommunal vuxenutbildning som särskild utbildning som motsvarar träningsskolan
     - _VUXSARGY_ - Kommunal vuxenutbildning som särskild utbildning på gymnasial nivå
     - _SFI_ - Utbildning i svenska för invandrare
     - _SARVUX_ - Särskild utbildning för vuxna 
     - _SARVUXGR_ - Särskild utbildning för vuxna på grundläggande nivå
     - _SARVUXGY_ - Särskild utbildning för vuxna på gymnasial nivå
     - _SFI_ - Kommunal vuxenutbildning i svenska för invandrare 
     - _KU_ - Kulturskola 
     - _YH_ - Yrkeshögskola 
     - _FHS_ - Folkhögskola 
     - _STF_ - Studieförbund 
     - _KKU_ - Konst- och kulturutbildning 
     - _HS_ - Högskola 
     - _ABU_ - Arbetsmarknadsutbildning 
     - _AU_ - Annan undervisning
    
    *
    * @var string
    */
    protected $schoolType;
    /**
     * En kod för ämnet som används bland annat i lärarlegitimation och i Skolverkets kurs- och läroplaner, till exempel GRGRMAT01. Notera att detta värde *ej* är den kortare ämndesbeteckningen, exempelvis _MA_, utan indikerar inte bara ämne utan även vilken läroplan som avses, såsom i exemplet ovan Grundskolan. För ämnen som inte definieras av Skolverket används valfri kod.
     *
     * @var string
     */
    protected $subjectCode;
    /**
     * Ämnets namn, exempelvis Matematik.
     *
     * @var string
     */
    protected $subjectName;
    /**
     * Ämnets beteckning, exempelvis MA, MLARA.
     *
     * @var string
     */
    protected $subjectDesignation;
    /**
     * Kurskod enligt Skolverket, om det är en officiell kurs, eller annars efter eget val.
     *
     * @var string
     */
    protected $courseCode;
    /**
     * Kursens namn, exempelvis Matematik 1a.
     *
     * @var string
     */
    protected $courseName;
    /**
     * Start för årskursintervall för undervisningens innehåll.
     *
     * @var int
     */
    protected $startSchoolYear;
    /**
     * Slut för årskursintervall för undervisningens innehåll.
     *
     * @var int
     */
    protected $endSchoolYear;
    /**
     * Antalet poäng för en specifik kurs. Exempelvis 100 poäng.
     *
     * @var int
     */
    protected $points;
    /**
     * Anger vilken läroplan aktiviteten avser. För vissa skolformer saknas läroplan.
     *
     * @var string
     */
    protected $curriculum;
    /**
     * Språkkod för moderna språk och modersmål. Enligt ISO 639-3.
     *
     * @var string
     */
    protected $languageCode;
    /**
     * Beskrivning av innehållet i en specialiseringskurs på gymnasiet.
     *
     * @var SpecialisationCourseContent
     */
    protected $specialisationCourseContent;
    /**
     * Attributet anger om ämnet är ett officiellt ämne från Skolverket eller annan myndighet. Icke officiella ämnen kan skapas för andra ändamål än undervisning.
     *
     * @var bool
     */
    protected $official;
    /**
     * 
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * 
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
    * Följande värden används för att beskriva skolform:
     - _FS_ - Förskola 
     - _FKLASS_ - Förskoleklass 
     - _FTH_ - Fritidshem
     - _OPPFTH_ - Öppen fritidsverksamhet 
     - _GR_ - Grundskola 
     - _GRS_ - Grundsärskola 
     - _TR_ - Träningsskolan
     - _SP_ - Specialskola 
     - _SAM_ - Sameskola 
     - _GY_ - Gymnasieskola 
     - _GYS_ - Gymnasiesärskola 
     - _VUX_ - Kommunal vuxenutbildning
     - _VUXSFI_ - Kommunal vuxenutbildning i svenska för invandrare
     - _VUXGR_ - Kommunal vuxenutbildning på grundläggande nivå
     - _VUXGY_ - Kommunal vuxenutbildning på gymnasial nivå
     - _VUXSARGR_ - Kommunal vuxenutbildning som särskild utbildning på grundläggande nivå
     - _VUXSARTR_ - Kommunal vuxenutbildning som särskild utbildning som motsvarar träningsskolan
     - _VUXSARGY_ - Kommunal vuxenutbildning som särskild utbildning på gymnasial nivå
     - _SFI_ - Utbildning i svenska för invandrare
     - _SARVUX_ - Särskild utbildning för vuxna 
     - _SARVUXGR_ - Särskild utbildning för vuxna på grundläggande nivå
     - _SARVUXGY_ - Särskild utbildning för vuxna på gymnasial nivå
     - _SFI_ - Kommunal vuxenutbildning i svenska för invandrare 
     - _KU_ - Kulturskola 
     - _YH_ - Yrkeshögskola 
     - _FHS_ - Folkhögskola 
     - _STF_ - Studieförbund 
     - _KKU_ - Konst- och kulturutbildning 
     - _HS_ - Högskola 
     - _ABU_ - Arbetsmarknadsutbildning 
     - _AU_ - Annan undervisning
    
    *
    * @return string
    */
    public function getSchoolType(): string
    {
        return $this->schoolType;
    }
    /**
    * Följande värden används för att beskriva skolform:
     - _FS_ - Förskola 
     - _FKLASS_ - Förskoleklass 
     - _FTH_ - Fritidshem
     - _OPPFTH_ - Öppen fritidsverksamhet 
     - _GR_ - Grundskola 
     - _GRS_ - Grundsärskola 
     - _TR_ - Träningsskolan
     - _SP_ - Specialskola 
     - _SAM_ - Sameskola 
     - _GY_ - Gymnasieskola 
     - _GYS_ - Gymnasiesärskola 
     - _VUX_ - Kommunal vuxenutbildning
     - _VUXSFI_ - Kommunal vuxenutbildning i svenska för invandrare
     - _VUXGR_ - Kommunal vuxenutbildning på grundläggande nivå
     - _VUXGY_ - Kommunal vuxenutbildning på gymnasial nivå
     - _VUXSARGR_ - Kommunal vuxenutbildning som särskild utbildning på grundläggande nivå
     - _VUXSARTR_ - Kommunal vuxenutbildning som särskild utbildning som motsvarar träningsskolan
     - _VUXSARGY_ - Kommunal vuxenutbildning som särskild utbildning på gymnasial nivå
     - _SFI_ - Utbildning i svenska för invandrare
     - _SARVUX_ - Särskild utbildning för vuxna 
     - _SARVUXGR_ - Särskild utbildning för vuxna på grundläggande nivå
     - _SARVUXGY_ - Särskild utbildning för vuxna på gymnasial nivå
     - _SFI_ - Kommunal vuxenutbildning i svenska för invandrare 
     - _KU_ - Kulturskola 
     - _YH_ - Yrkeshögskola 
     - _FHS_ - Folkhögskola 
     - _STF_ - Studieförbund 
     - _KKU_ - Konst- och kulturutbildning 
     - _HS_ - Högskola 
     - _ABU_ - Arbetsmarknadsutbildning 
     - _AU_ - Annan undervisning
    
    *
    * @param string $schoolType
    *
    * @return self
    */
    public function setSchoolType(string $schoolType): self
    {
        $this->initialized['schoolType'] = true;
        $this->schoolType = $schoolType;
        return $this;
    }
    /**
     * En kod för ämnet som används bland annat i lärarlegitimation och i Skolverkets kurs- och läroplaner, till exempel GRGRMAT01. Notera att detta värde *ej* är den kortare ämndesbeteckningen, exempelvis _MA_, utan indikerar inte bara ämne utan även vilken läroplan som avses, såsom i exemplet ovan Grundskolan. För ämnen som inte definieras av Skolverket används valfri kod.
     *
     * @return string
     */
    public function getSubjectCode(): string
    {
        return $this->subjectCode;
    }
    /**
     * En kod för ämnet som används bland annat i lärarlegitimation och i Skolverkets kurs- och läroplaner, till exempel GRGRMAT01. Notera att detta värde *ej* är den kortare ämndesbeteckningen, exempelvis _MA_, utan indikerar inte bara ämne utan även vilken läroplan som avses, såsom i exemplet ovan Grundskolan. För ämnen som inte definieras av Skolverket används valfri kod.
     *
     * @param string $subjectCode
     *
     * @return self
     */
    public function setSubjectCode(string $subjectCode): self
    {
        $this->initialized['subjectCode'] = true;
        $this->subjectCode = $subjectCode;
        return $this;
    }
    /**
     * Ämnets namn, exempelvis Matematik.
     *
     * @return string
     */
    public function getSubjectName(): string
    {
        return $this->subjectName;
    }
    /**
     * Ämnets namn, exempelvis Matematik.
     *
     * @param string $subjectName
     *
     * @return self
     */
    public function setSubjectName(string $subjectName): self
    {
        $this->initialized['subjectName'] = true;
        $this->subjectName = $subjectName;
        return $this;
    }
    /**
     * Ämnets beteckning, exempelvis MA, MLARA.
     *
     * @return string
     */
    public function getSubjectDesignation(): string
    {
        return $this->subjectDesignation;
    }
    /**
     * Ämnets beteckning, exempelvis MA, MLARA.
     *
     * @param string $subjectDesignation
     *
     * @return self
     */
    public function setSubjectDesignation(string $subjectDesignation): self
    {
        $this->initialized['subjectDesignation'] = true;
        $this->subjectDesignation = $subjectDesignation;
        return $this;
    }
    /**
     * Kurskod enligt Skolverket, om det är en officiell kurs, eller annars efter eget val.
     *
     * @return string
     */
    public function getCourseCode(): string
    {
        return $this->courseCode;
    }
    /**
     * Kurskod enligt Skolverket, om det är en officiell kurs, eller annars efter eget val.
     *
     * @param string $courseCode
     *
     * @return self
     */
    public function setCourseCode(string $courseCode): self
    {
        $this->initialized['courseCode'] = true;
        $this->courseCode = $courseCode;
        return $this;
    }
    /**
     * Kursens namn, exempelvis Matematik 1a.
     *
     * @return string
     */
    public function getCourseName(): string
    {
        return $this->courseName;
    }
    /**
     * Kursens namn, exempelvis Matematik 1a.
     *
     * @param string $courseName
     *
     * @return self
     */
    public function setCourseName(string $courseName): self
    {
        $this->initialized['courseName'] = true;
        $this->courseName = $courseName;
        return $this;
    }
    /**
     * Start för årskursintervall för undervisningens innehåll.
     *
     * @return int
     */
    public function getStartSchoolYear(): int
    {
        return $this->startSchoolYear;
    }
    /**
     * Start för årskursintervall för undervisningens innehåll.
     *
     * @param int $startSchoolYear
     *
     * @return self
     */
    public function setStartSchoolYear(int $startSchoolYear): self
    {
        $this->initialized['startSchoolYear'] = true;
        $this->startSchoolYear = $startSchoolYear;
        return $this;
    }
    /**
     * Slut för årskursintervall för undervisningens innehåll.
     *
     * @return int
     */
    public function getEndSchoolYear(): int
    {
        return $this->endSchoolYear;
    }
    /**
     * Slut för årskursintervall för undervisningens innehåll.
     *
     * @param int $endSchoolYear
     *
     * @return self
     */
    public function setEndSchoolYear(int $endSchoolYear): self
    {
        $this->initialized['endSchoolYear'] = true;
        $this->endSchoolYear = $endSchoolYear;
        return $this;
    }
    /**
     * Antalet poäng för en specifik kurs. Exempelvis 100 poäng.
     *
     * @return int
     */
    public function getPoints(): int
    {
        return $this->points;
    }
    /**
     * Antalet poäng för en specifik kurs. Exempelvis 100 poäng.
     *
     * @param int $points
     *
     * @return self
     */
    public function setPoints(int $points): self
    {
        $this->initialized['points'] = true;
        $this->points = $points;
        return $this;
    }
    /**
     * Anger vilken läroplan aktiviteten avser. För vissa skolformer saknas läroplan.
     *
     * @return string
     */
    public function getCurriculum(): string
    {
        return $this->curriculum;
    }
    /**
     * Anger vilken läroplan aktiviteten avser. För vissa skolformer saknas läroplan.
     *
     * @param string $curriculum
     *
     * @return self
     */
    public function setCurriculum(string $curriculum): self
    {
        $this->initialized['curriculum'] = true;
        $this->curriculum = $curriculum;
        return $this;
    }
    /**
     * Språkkod för moderna språk och modersmål. Enligt ISO 639-3.
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }
    /**
     * Språkkod för moderna språk och modersmål. Enligt ISO 639-3.
     *
     * @param string $languageCode
     *
     * @return self
     */
    public function setLanguageCode(string $languageCode): self
    {
        $this->initialized['languageCode'] = true;
        $this->languageCode = $languageCode;
        return $this;
    }
    /**
     * Beskrivning av innehållet i en specialiseringskurs på gymnasiet.
     *
     * @return SpecialisationCourseContent
     */
    public function getSpecialisationCourseContent(): SpecialisationCourseContent
    {
        return $this->specialisationCourseContent;
    }
    /**
     * Beskrivning av innehållet i en specialiseringskurs på gymnasiet.
     *
     * @param SpecialisationCourseContent $specialisationCourseContent
     *
     * @return self
     */
    public function setSpecialisationCourseContent(SpecialisationCourseContent $specialisationCourseContent): self
    {
        $this->initialized['specialisationCourseContent'] = true;
        $this->specialisationCourseContent = $specialisationCourseContent;
        return $this;
    }
    /**
     * Attributet anger om ämnet är ett officiellt ämne från Skolverket eller annan myndighet. Icke officiella ämnen kan skapas för andra ändamål än undervisning.
     *
     * @return bool
     */
    public function getOfficial(): bool
    {
        return $this->official;
    }
    /**
     * Attributet anger om ämnet är ett officiellt ämne från Skolverket eller annan myndighet. Icke officiella ämnen kan skapas för andra ändamål än undervisning.
     *
     * @param bool $official
     *
     * @return self
     */
    public function setOfficial(bool $official): self
    {
        $this->initialized['official'] = true;
        $this->official = $official;
        return $this;
    }
}