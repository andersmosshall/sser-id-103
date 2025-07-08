<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class Programme extends \ArrayObject
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
     * Identifierare för programmet.
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
     * Program-/inriktningens namn.
     *
     * @var string
     */
    protected $name;
    /**
     * Typ av program.
     *
     * @var string
     */
    protected $type;
    /**
     * 
     *
     * @var mixed
     */
    protected $parentProgramme;
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
     * Program-/inriktningskod(studievägskod). För gymnasieskolans program/inriktningar måste denna finnas och vara enligt Skolverkets definition.
     *
     * @var string
     */
    protected $code;
    /**
     * Kurser/ämnen som ingår i utbildningen.
     *
     * @var list<ProgrammeContentInner>
     */
    protected $content;
    /**
     * Identifierare för programmet.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Identifierare för programmet.
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
     * Program-/inriktningens namn.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * Program-/inriktningens namn.
     *
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->initialized['name'] = true;
        $this->name = $name;
        return $this;
    }
    /**
     * Typ av program.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    /**
     * Typ av program.
     *
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->initialized['type'] = true;
        $this->type = $type;
        return $this;
    }
    /**
     * 
     *
     * @return mixed
     */
    public function getParentProgramme()
    {
        return $this->parentProgramme;
    }
    /**
     * 
     *
     * @param mixed $parentProgramme
     *
     * @return self
     */
    public function setParentProgramme($parentProgramme): self
    {
        $this->initialized['parentProgramme'] = true;
        $this->parentProgramme = $parentProgramme;
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
     * Program-/inriktningskod(studievägskod). För gymnasieskolans program/inriktningar måste denna finnas och vara enligt Skolverkets definition.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }
    /**
     * Program-/inriktningskod(studievägskod). För gymnasieskolans program/inriktningar måste denna finnas och vara enligt Skolverkets definition.
     *
     * @param string $code
     *
     * @return self
     */
    public function setCode(string $code): self
    {
        $this->initialized['code'] = true;
        $this->code = $code;
        return $this;
    }
    /**
     * Kurser/ämnen som ingår i utbildningen.
     *
     * @return list<ProgrammeContentInner>
     */
    public function getContent(): array
    {
        return $this->content;
    }
    /**
     * Kurser/ämnen som ingår i utbildningen.
     *
     * @param list<ProgrammeContentInner> $content
     *
     * @return self
     */
    public function setContent(array $content): self
    {
        $this->initialized['content'] = true;
        $this->content = $content;
        return $this;
    }
}