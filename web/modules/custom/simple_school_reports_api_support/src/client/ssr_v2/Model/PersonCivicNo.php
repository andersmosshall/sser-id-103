<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Model;

class PersonCivicNo extends \ArrayObject
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
    * Svenskt personnummer, tilldelat personnummer eller Skatteverkets
    samordningsnummer för personen. **Ska** anges med 12 siffror utan
    separatorer. _Exempel: 200112240123_
    
    Samordningsnummer ska anges med 12 siffror utan separator.
    Födelsedagen adderas med talet 60, det vill säga någon född den 24
    i en månad får talet 84 som dag. _Exempel: 200112840123_
    
    Saknas både personnummer och samordningsnummer så förekommer det att
    "tillfälligt personnummer" definieras i elevregistret. Dessa är möjliga
    att beskriva i detta fält och i så fall tillåts de två första positionerna
    efter datumdelen att vara bokstäver. _Exempel: 20130101TF01_
    
    *
    * @var string
    */
    protected $value;
    /**
     * Landskod för det land som personnumret härstammar från, enligt ISO 3166-1 alpha-2.
     *
     * @var string
     */
    protected $nationality = 'SE';
    /**
    * Svenskt personnummer, tilldelat personnummer eller Skatteverkets
    samordningsnummer för personen. **Ska** anges med 12 siffror utan
    separatorer. _Exempel: 200112240123_
    
    Samordningsnummer ska anges med 12 siffror utan separator.
    Födelsedagen adderas med talet 60, det vill säga någon född den 24
    i en månad får talet 84 som dag. _Exempel: 200112840123_
    
    Saknas både personnummer och samordningsnummer så förekommer det att
    "tillfälligt personnummer" definieras i elevregistret. Dessa är möjliga
    att beskriva i detta fält och i så fall tillåts de två första positionerna
    efter datumdelen att vara bokstäver. _Exempel: 20130101TF01_
    
    *
    * @return string
    */
    public function getValue(): string
    {
        return $this->value;
    }
    /**
    * Svenskt personnummer, tilldelat personnummer eller Skatteverkets
    samordningsnummer för personen. **Ska** anges med 12 siffror utan
    separatorer. _Exempel: 200112240123_
    
    Samordningsnummer ska anges med 12 siffror utan separator.
    Födelsedagen adderas med talet 60, det vill säga någon född den 24
    i en månad får talet 84 som dag. _Exempel: 200112840123_
    
    Saknas både personnummer och samordningsnummer så förekommer det att
    "tillfälligt personnummer" definieras i elevregistret. Dessa är möjliga
    att beskriva i detta fält och i så fall tillåts de två första positionerna
    efter datumdelen att vara bokstäver. _Exempel: 20130101TF01_
    
    *
    * @param string $value
    *
    * @return self
    */
    public function setValue(string $value): self
    {
        $this->initialized['value'] = true;
        $this->value = $value;
        return $this;
    }
    /**
     * Landskod för det land som personnumret härstammar från, enligt ISO 3166-1 alpha-2.
     *
     * @return string
     */
    public function getNationality(): string
    {
        return $this->nationality;
    }
    /**
     * Landskod för det land som personnumret härstammar från, enligt ISO 3166-1 alpha-2.
     *
     * @param string $nationality
     *
     * @return self
     */
    public function setNationality(string $nationality): self
    {
        $this->initialized['nationality'] = true;
        $this->nationality = $nationality;
        return $this;
    }
}