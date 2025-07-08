<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Enrolment extends \ArrayObject
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
     * @var SchoolUnitReference
     */
    protected $enroledAt;
    /**
     * Värdet årskurs anger det år efter skolstarten för vilket en student följer undervisningen.
     *
     * @var int
     */
    protected $schoolYear;
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
     * Startdatum för inskrivningen (RFC 3339-format, t.ex. "2016-10-15").Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för inskrivningen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
     * Inskrivningen har avbrutits i förväg.
     *
     * @var bool
     */
    protected $cancelled;
    /**
     * Studievägskod för den aktuella inskrivningen.
     *
     * @var string
     */
    protected $educationCode;
    /**
     * 
     *
     * @var mixed
     */
    protected $programme;
    /**
     * Kompletterande information angående innehåll i elevens utbildning, används som avgränsning av ett visst utbildningsalternativ för exempelvis lärlingsutbildning.
     *
     * @var string
     */
    protected $specification;
    /**
     * 
     *
     * @return SchoolUnitReference
     */
    public function getEnroledAt(): SchoolUnitReference
    {
        return $this->enroledAt;
    }
    /**
     * 
     *
     * @param SchoolUnitReference $enroledAt
     *
     * @return self
     */
    public function setEnroledAt(SchoolUnitReference $enroledAt): self
    {
        $this->initialized['enroledAt'] = true;
        $this->enroledAt = $enroledAt;
        return $this;
    }
    /**
     * Värdet årskurs anger det år efter skolstarten för vilket en student följer undervisningen.
     *
     * @return int
     */
    public function getSchoolYear(): int
    {
        return $this->schoolYear;
    }
    /**
     * Värdet årskurs anger det år efter skolstarten för vilket en student följer undervisningen.
     *
     * @param int $schoolYear
     *
     * @return self
     */
    public function setSchoolYear(int $schoolYear): self
    {
        $this->initialized['schoolYear'] = true;
        $this->schoolYear = $schoolYear;
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
     * Startdatum för inskrivningen (RFC 3339-format, t.ex. "2016-10-15").Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för inskrivningen (RFC 3339-format, t.ex. "2016-10-15").Inkluderande.
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
     * Slutdatum för inskrivningen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för inskrivningen (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Inskrivningen har avbrutits i förväg.
     *
     * @return bool
     */
    public function getCancelled(): bool
    {
        return $this->cancelled;
    }
    /**
     * Inskrivningen har avbrutits i förväg.
     *
     * @param bool $cancelled
     *
     * @return self
     */
    public function setCancelled(bool $cancelled): self
    {
        $this->initialized['cancelled'] = true;
        $this->cancelled = $cancelled;
        return $this;
    }
    /**
     * Studievägskod för den aktuella inskrivningen.
     *
     * @return string
     */
    public function getEducationCode(): string
    {
        return $this->educationCode;
    }
    /**
     * Studievägskod för den aktuella inskrivningen.
     *
     * @param string $educationCode
     *
     * @return self
     */
    public function setEducationCode(string $educationCode): self
    {
        $this->initialized['educationCode'] = true;
        $this->educationCode = $educationCode;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getProgramme()
    {
        return $this->programme;
    }
    /**
     * 
     *
     * @param mixed $programme
     *
     * @return self
     */
    public function setProgramme($programme): self
    {
        $this->initialized['programme'] = true;
        $this->programme = $programme;
        return $this;
    }
    /**
     * Kompletterande information angående innehåll i elevens utbildning, används som avgränsning av ett visst utbildningsalternativ för exempelvis lärlingsutbildning.
     *
     * @return string
     */
    public function getSpecification(): string
    {
        return $this->specification;
    }
    /**
     * Kompletterande information angående innehåll i elevens utbildning, används som avgränsning av ett visst utbildningsalternativ för exempelvis lärlingsutbildning.
     *
     * @param string $specification
     *
     * @return self
     */
    public function setSpecification(string $specification): self
    {
        $this->initialized['specification'] = true;
        $this->specification = $specification;
        return $this;
    }
}