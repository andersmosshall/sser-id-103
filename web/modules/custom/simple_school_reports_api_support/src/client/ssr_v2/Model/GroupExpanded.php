<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class GroupExpanded extends \ArrayObject
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
     * Identifierare för gruppen
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
     * Gruppens benämning.
     *
     * @var string
     */
    protected $displayName;
    /**
     * Startdatum för gruppens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $startDate;
    /**
     * Slutdatum för gruppens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @var \DateTime
     */
    protected $endDate;
    /**
    * Grupptyp anger vad en grupp ska användas till.
    Ett värdeförråd för att indikera anger vilka grupptyper som finns.
    * _Undervisning_ - Undervisningsgruppen är en grupp som har koppling
     till ett ämne eller en kurs, och som ska schemaläggas med undervisningstid
     ihop med en lärare.
    * _Klass_ - Klassen är en organisatorisk grupp med elever som är skolplacerade
     på en skola med skolform FSK, GR, GRS, SP, SAM, GY eller GYS.
     Varje elev ska vara placerad i endast en klass. Klassen kan schemaläggas
     med undervisning enligt timplan. "Klassföreståndare" kan beskrivas genom att
     koppla en _AssignmentRole_ av typen _Mentor_.
    * _Mentor_ - Grupp med elever vilka delar samma mentor/mentorer. 
     Mentor kopplas till gruppen genom en _AssignmentRole_.
    * _Provgrupp_ - Grupp med elever vilka ska genomföra ett eller flera prov. 
    * _Schema_ - Schemagrupper är grupper som utgör ett komplement till
     grupper av typen Undervisning. Schemagrupper ska schemaläggas för att
     åstadkomma anpassningar av schemat för enskilda elever eller grupper
     av elever. Exempel på schemagrupper är delgrupper och grupper som
     används för stödundervisning eller läxhjälp.
    * _Avdelning_ - Avdelningen är en grupp för placering inom skolformerna
     förskola eller fritidshem.
    * _Personalgrupp_ - En grupp vars medlemmar utgörs av personal.
    * _Övrigt_ - Övriga grupper är andra grupper som inte är något av ovanstående.
    
    *
    * @var string
    */
    protected $groupType;
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
     * 
     *
     * @var OrganisationReference
     */
    protected $organisation;
    /**
     * Gruppmedlemskap anger att en person är medlem i en grupp och vilken tidsperiod medlemskapet omfattar.
     *
     * @var list<GroupMembership>
     */
    protected $groupMemberships;
    /**
     * 
     *
     * @var GroupExpandedAllOfEmbedded
     */
    protected $embedded;
    /**
     * Identifierare för gruppen
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
    /**
     * Identifierare för gruppen
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
     * Gruppens benämning.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    /**
     * Gruppens benämning.
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
     * Startdatum för gruppens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }
    /**
     * Startdatum för gruppens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
     * Slutdatum för gruppens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }
    /**
     * Slutdatum för gruppens giltighetstid (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
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
    * Grupptyp anger vad en grupp ska användas till.
    Ett värdeförråd för att indikera anger vilka grupptyper som finns.
    * _Undervisning_ - Undervisningsgruppen är en grupp som har koppling
     till ett ämne eller en kurs, och som ska schemaläggas med undervisningstid
     ihop med en lärare.
    * _Klass_ - Klassen är en organisatorisk grupp med elever som är skolplacerade
     på en skola med skolform FSK, GR, GRS, SP, SAM, GY eller GYS.
     Varje elev ska vara placerad i endast en klass. Klassen kan schemaläggas
     med undervisning enligt timplan. "Klassföreståndare" kan beskrivas genom att
     koppla en _AssignmentRole_ av typen _Mentor_.
    * _Mentor_ - Grupp med elever vilka delar samma mentor/mentorer. 
     Mentor kopplas till gruppen genom en _AssignmentRole_.
    * _Provgrupp_ - Grupp med elever vilka ska genomföra ett eller flera prov. 
    * _Schema_ - Schemagrupper är grupper som utgör ett komplement till
     grupper av typen Undervisning. Schemagrupper ska schemaläggas för att
     åstadkomma anpassningar av schemat för enskilda elever eller grupper
     av elever. Exempel på schemagrupper är delgrupper och grupper som
     används för stödundervisning eller läxhjälp.
    * _Avdelning_ - Avdelningen är en grupp för placering inom skolformerna
     förskola eller fritidshem.
    * _Personalgrupp_ - En grupp vars medlemmar utgörs av personal.
    * _Övrigt_ - Övriga grupper är andra grupper som inte är något av ovanstående.
    
    *
    * @return string
    */
    public function getGroupType(): string
    {
        return $this->groupType;
    }
    /**
    * Grupptyp anger vad en grupp ska användas till.
    Ett värdeförråd för att indikera anger vilka grupptyper som finns.
    * _Undervisning_ - Undervisningsgruppen är en grupp som har koppling
     till ett ämne eller en kurs, och som ska schemaläggas med undervisningstid
     ihop med en lärare.
    * _Klass_ - Klassen är en organisatorisk grupp med elever som är skolplacerade
     på en skola med skolform FSK, GR, GRS, SP, SAM, GY eller GYS.
     Varje elev ska vara placerad i endast en klass. Klassen kan schemaläggas
     med undervisning enligt timplan. "Klassföreståndare" kan beskrivas genom att
     koppla en _AssignmentRole_ av typen _Mentor_.
    * _Mentor_ - Grupp med elever vilka delar samma mentor/mentorer. 
     Mentor kopplas till gruppen genom en _AssignmentRole_.
    * _Provgrupp_ - Grupp med elever vilka ska genomföra ett eller flera prov. 
    * _Schema_ - Schemagrupper är grupper som utgör ett komplement till
     grupper av typen Undervisning. Schemagrupper ska schemaläggas för att
     åstadkomma anpassningar av schemat för enskilda elever eller grupper
     av elever. Exempel på schemagrupper är delgrupper och grupper som
     används för stödundervisning eller läxhjälp.
    * _Avdelning_ - Avdelningen är en grupp för placering inom skolformerna
     förskola eller fritidshem.
    * _Personalgrupp_ - En grupp vars medlemmar utgörs av personal.
    * _Övrigt_ - Övriga grupper är andra grupper som inte är något av ovanstående.
    
    *
    * @param string $groupType
    *
    * @return self
    */
    public function setGroupType(string $groupType): self
    {
        $this->initialized['groupType'] = true;
        $this->groupType = $groupType;
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
     * 
     *
     * @return OrganisationReference
     */
    public function getOrganisation(): OrganisationReference
    {
        return $this->organisation;
    }
    /**
     * 
     *
     * @param OrganisationReference $organisation
     *
     * @return self
     */
    public function setOrganisation(OrganisationReference $organisation): self
    {
        $this->initialized['organisation'] = true;
        $this->organisation = $organisation;
        return $this;
    }
    /**
     * Gruppmedlemskap anger att en person är medlem i en grupp och vilken tidsperiod medlemskapet omfattar.
     *
     * @return list<GroupMembership>
     */
    public function getGroupMemberships(): array
    {
        return $this->groupMemberships;
    }
    /**
     * Gruppmedlemskap anger att en person är medlem i en grupp och vilken tidsperiod medlemskapet omfattar.
     *
     * @param list<GroupMembership> $groupMemberships
     *
     * @return self
     */
    public function setGroupMemberships(array $groupMemberships): self
    {
        $this->initialized['groupMemberships'] = true;
        $this->groupMemberships = $groupMemberships;
        return $this;
    }
    /**
     * 
     *
     * @return GroupExpandedAllOfEmbedded
     */
    public function getEmbedded(): GroupExpandedAllOfEmbedded
    {
        return $this->embedded;
    }
    /**
     * 
     *
     * @param GroupExpandedAllOfEmbedded $embedded
     *
     * @return self
     */
    public function setEmbedded(GroupExpandedAllOfEmbedded $embedded): self
    {
        $this->initialized['embedded'] = true;
        $this->embedded = $embedded;
        return $this;
    }
}