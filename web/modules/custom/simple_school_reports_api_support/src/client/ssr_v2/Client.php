<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2;

class Client extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Client
{
    /**
    * 
    *
    * @param array $queryParameters {
    *     @var array $parent Begränsa urvalet till utpekade organisations-ID:n.
    *     @var array $schoolUnitCode Begränsa urvalet till de skolenheter som har den angivna Skolenhetskoden. En Identifierare för skolenheten enligt Skolverket.
    
    *     @var array $organisationCode Begränsa urvalet till de organisationselement som har den angivna koden.
    
    *     @var string $municipalityCode Begränsa urvalet till de organisationselement som har angiven kommunkod.
    
    *     @var array $type Begränsa urvalet till utpekade typ.
    *     @var array $schoolTypes Begränsa urvalet till de organisationselement som har den angivna skolformen.
    
    *     @var string $startDate.onOrBefore Begränsa urvalet till organisationselement som har ett startDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $startDate.onOrAfter Begränsa urvalet till organisationselement som har ett startDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $endDate.onOrBefore Begränsa urvalet till organisationselement som har ett endDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $endDate.onOrAfter Begränsa urvalet till organisationselement som har ett endDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
    *     @var string $sortkey 
    *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
    
    *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
    
    * }
    * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetOrganisationBadRequestException
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetOrganisationForbiddenException
    *
    * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisations|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
    */
    public function getOrganisation(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetOrganisation($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta organisationer en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många organisationer på en gång genom att skicka ett anrop med en lista med önskade organisationer.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationsLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostOrganisationsLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostOrganisationsLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postOrganisationsLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationsLookupPostRequest $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostOrganisationsLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för organisationen som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetOrganisationByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetOrganisationByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetOrganisationByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getOrganisationById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetOrganisationById($id, $queryParameters), $fetch);
    }
    /**
    * 
    *
    * @param array $queryParameters {
    *     @var array $nameContains Begränsa urvalet till de personer vars namn innehåller något av parameterns värden.
    Sökningen **ska** ske shiftlägesokänsligt och värdet kan förekomma var som helst i något av alla tre namnfälten. 
    Anges flera värden så måste samtliga värden matcha minst ett av namnfälten.
    
    Exempel: [ "Pa", "gens" ] kommer matcha Palle Girgensohn.
    
    *     @var string $civicNo Begränsa urvalet till den person vars civicNo matchar parameterns värde.
    *     @var string $eduPersonPrincipalName Begränsa urvalet till den person vars eduPersonPrincipalNames matchar parameterns värde.
    *     @var string $identifier.value Begränsa urvalet till den person vilka har ett värde i `externalIdentifiers.value` som matchar parameterns värde. Kan kombineras med parametern `identifier.context` för att begränsa matchningen till en specifik typ av indentifierare.
    *     @var string $identifier.context Begränsa urvalet till den person vilka har ett värde i `externalIdentifiers.context` som matchar parameterns värde. Kombineras vanligtvis med `identifier.value` parametern.
    *     @var string $relationship.entity.type Begränsa urvalet till de personer som har en denna typ av relation till andra entititeter. 
    Denna parameter styr vilket entitetstyp som övriga relationship-parametrar filterar på.
    Anges inga andra parametrar så returneras personer som har en relation av denna typ.
    
    Möjliga relationer:
    - _enrolment_ - filtrerar utifrån elever inskrivning.
    - _duty_ - filtrerar utifrån personer som har minst en tjänstgöring.
    - _placement.child_ - filtrerar utifrån barn som har minst en placering.
    - _placement.owner_ - filtrerar utifrån personer som satta som ägare av minst en placering.
    - _responsibleFor.enrolment_ - filterar utifrån personer som har en _"responsibleFor"_-relation, dvs är en vårdnadshavare eller annan ansvarig vuxen, till en elev med minst en inskrivning.
    - _responsibleFor.placement_ - filterar utifrån personer som har en _"responsibleFor"_-relation, dvs är en vårdnadshavare eller annan ansvarig vuxen, till ett barn med minst en placering.
     Notera att oftast är det bättre att använda _placement.owner_ än denna parameter.
    - _groupMembership_ - filtrerar utifrån gruppmedlemsskap
    
    Detta kan kombineras med `relationship.startDate.onOrBefore` och `relationship.endDate.onOrAfter` för att begränsa till aktiva relationer.
    
    *     @var string $relationship.organisation Begränsa urvalet till de personer som har en relation till angivet organisationselement (vanligtvis en skolenhet). 
    För att begränsa till en viss relationtyp används parametern `relationship.entity.type`.
    
    Följande fält/relationer används vid filtreringen:
    - _enrolment_ - poster matchandes `person.enrolment.enroledAt`
    - _duty_ - person poster matchandes `duty.person` i en lista filtrerad utifrån `duty.dutyAt`.
    - _placement.child_ - person poster matchandes `placement.child` i en lista filtrerad utifrån `placement.placedAt`.
    - _placement.owner_ - person poster matchandes `placement.owner` i en lista filtrerad utifrån `placement.placedAt`.
    - _responsibleFor.enrolment_ - person poster matchandes `person.responsibles` i en lista filtrerad utifrån `person.enrolment.enroledAt`.
    - _responsibleFor.placement_ - person poster matchandes `person.responsibles` i en lista med person poster som i sin tur matchar `placement.child` i en lista filtrerad utifrån `placement.placedAt`.
    - _groupMembership_ - person poster matchandes `person.groupMemberships`
    
    *     @var string $relationship.startDate.onOrBefore Begränsa urvalet av personer till de som har relationer med startDate innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett startDate som ej är satt, tas alltid med. 
    För att begränsa till en viss relationtyp används parametern `relationship.entity.type`.
    
    Följande fält/relationer används vid filtreringen:
    - _enrolment_ - poster matchandes `person.enrolment.startDate`
    - _duty_ - person poster matchandes `duty.person` i en lista filtrerad utifrån `duty.startDate`.
    - _placement.child_ - person poster matchandes `placement.child` i en lista filtrerad utifrån `placement.startDate`.
    - _placement.owner_ - person poster matchandes `placement.owner` i en lista filtrerad utifrån `placement.startDate`.
    - _responsibleFor.enrolment_ - person poster matchandes `person.responsibles` i en lista filtrerad utifrån `person.enrolment.startDate`.
    - _responsibleFor.placement_ - person poster matchandes `person.responsibles` i en lista med person poster som i sin tur matchar `placement.child` i en lista filtrerad utifrån `placement.startDate`.
    - _groupMembership_ - poster matchandes `group.groupMemberships.person` eller `group.assignmentRole.duty.person` i en lista filtrerad utifrån `group.groupMemberships.startDate` eller `group.assignmentRole.startDate`.
     
    Detta kan kombineras med _relationship.endDate.onOrAfter_ för att begränsa till aktiva relationer.
    
    *     @var string $relationship.startDate.onOrAfter Begränsa urvalet av personer till de som har relationer med startDate efter eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett startDate som ej är satt, tas alltid med. 
    För att begränsa till en viss relationtyp används parametern `relationship.entity.type`.
    
    Följande fält/relationer används vid filtreringen:
    - _enrolment_ - poster matchandes `person.enrolment.startDate`
    - _duty_ - person poster matchandes `duty.person` i en lista filtrerad utifrån `duty.startDate`.
    - _placement.child_ - person poster matchandes `placement.child` i en lista filtrerad utifrån `placement.startDate`.
    - _placement.owner_ - person poster matchandes `placement.owner` i en lista filtrerad utifrån `placement.startDate`.
    - _responsibleFor.enrolment_ - person poster matchandes `person.responsibles` i en lista filtrerad utifrån `person.enrolment.startDate`.
    - _responsibleFor.placement_ - person poster matchandes `person.responsibles` i en lista med person poster som i sin tur matchar `placement.child` i en lista filtrerad utifrån `placement.startDate`.
    - _groupMembership_ - poster matchande `group.groupMemberships.person` eller `group.assignmentRole.duty.person` i en lista filtrerad utifrån `group.groupMemberships.startDate` eller `group.assignmentRole.startDate`.
    
    *     @var string $relationship.endDate.onOrBefore Begränsa urvalet av personer till de som har relationer med endDate innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    För att begränsa till en viss relationtyp används parametern `relationship.entity.type`.
    
    Följande fält/relationer används vid filtreringen:
    - _enrolment_ - poster matchandes `person.enrolment.endDate`
    - _duty_ - person poster matchandes `duty.person` i en lista filtrerad utifrån `duty.endDate`.
    - _placement.child_ - person poster matchandes `placement.child` i en lista filtrerad utifrån `placement.endDate`.
    - _placement.owner_ - person poster matchandes `placement.owner` i en lista filtrerad utifrån `placement.endDate`.
    - _responsibleFor.enrolment_ - person poster matchandes `person.responsibles` i en lista filtrerad utifrån `person.enrolment.endDate`.
    - _responsibleFor.placement_ - person poster matchandes `person.responsibles` i en lista med person poster som i sin tur matchar `placement.child` i en lista filtrerad utifrån `placement.endDate`.
    - _groupMembership_ - poster matchande `group.groupMemberships.person` eller `group.assignmentRole.duty.person` i en lista filtrerad utifrån `group.groupMemberships.endDate` eller `group.assignmentRole.endDate`.
    
    *     @var string $relationship.endDate.onOrAfter Begränsa urvalet av personer till de som har relationer med endDate efter eller på det angivna datumet (RFC 3339-format,t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    För att begränsa till en viss relationtyp används parametern `relationship.entity.type`.
    
    Flöjande fält/relationer används vid filtreringen:
    - _enrolment_ - poster matchandes `person.enrolment.endDate`
    - _duty_ - person poster matchandes `duty.person` i en lista filtrerad utifrån `duty.endDate`.
    - _placement.child_ - person poster matchandes `placement.child` i en lista filtrerad utifrån `placement.endDate`.
    - _placement.owner_ - person poster matchandes `placement.owner` i en lista filtrerad utifrån `placement.endDate`.
    - _responsibleFor.enrolment_ - person poster matchandes `person.responsibles` i en lista filtrerad utifrån `person.enrolment.endDate`.
    - _responsibleFor.placement_ - person poster matchandes `person.responsibles` i en lista med person poster som i sin tur matchar `placement.child` i en lista filtrerad utifrån `placement.endDate`.
    - _groupMembership_ - poster matchande `group.groupMemberships.person` eller `group.assignmentRole.duty.person` i en lista filtrerad utifrån `group.groupMemberships.endDate` eller `group.assignmentRole.endDate`.
    
    Detta kan kombineras med _relationship.startDate.onOrBefore_ för att begränsa till aktiva relationer.
    
    *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var array $expand Beskriver om expanderade data ska hämtas
    *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
    *     @var string $sortkey Anger hur resultatet ska sorteras.
    *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
    
    *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
    
    * }
    * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonBadRequestException
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonForbiddenException
    *
    * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
    */
    public function getPerson(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetPerson($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta personer en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många personer på en gång genom att skicka ett anrop med en lista med önskade personer.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostPersonsLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostPersonsLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpanded[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postPersonsLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsLookupPostRequest $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostPersonsLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för personen som ska hämtas
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getPersonById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetPersonById($id, $queryParameters), $fetch);
    }
    /**
    * 
    *
    * @param array $queryParameters {
    *     @var string $organisation Begränsa urvalet till de barn som har en placering (placedAt) på angivet organisationselement.  Detta kan kombineras med startDate.onOrBefore och endDate.onOrAfter för att begränsa till aktiva placeringar.
    
    *     @var string $group Begränsa urvalet till de barn som har en placering på angiven grupp. Detta kan kombineras med startDate.onOrBefore och endDate.onOrAfter för att begränsa till aktiva placeringar.
    
    *     @var string $startDate.onOrBefore Begränsa urvalet till placeringar som har ett startDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $startDate.onOrAfter Begränsa urvalet till placeringar som har ett startDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $endDate.onOrBefore Begränsa urvalet till placeringar som har ett endDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $endDate.onOrAfter Begränsa urvalet till placeringar som har ett endDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $child Begränsa urvalet till ett barn. Detta kan kombineras med startDate.onOrBefore och endDate.onOrAfter för att begränsa till aktiva placeringar.
    
    *     @var string $owner Begränsa urvalet till placeringar med denna ägare. Detta kan kombineras med startDate.onOrAfter och endDate.onOrBefore för att begränsa till aktiva placeringar.
    
    *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var array $expand Beskriver om expanderade data ska hämtas
    *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
    *     @var string $sortkey Anger hur resultatet ska sorteras.
    *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
    
    *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
    
    * }
    * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPlacementBadRequestException
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPlacementForbiddenException
    *
    * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Placements|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
    */
    public function getPlacement(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetPlacement($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta placeringar en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många placeringar på en gång genom att skicka ett anrop med en lista med önskade placeringar.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementsLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostPlacementsLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostPlacementsLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementExpanded[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postPlacementsLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementsLookupPostRequest $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostPlacementsLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för placering som ska hämtas
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPlacementByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPlacementByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPlacementByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getPlacementById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetPlacementById($id, $queryParameters), $fetch);
    }
    /**
    * 
    *
    * @param array $queryParameters {
    *     @var string $organisation Begränsa urvalet till de tjänstgöringar som är kopplade till ett organisationselement eller underliggande element.
    
    *     @var string $dutyRole Begränsta urvalet till de tjänstgöringar som matchar roll
    *     @var string $person Begränsa urvalet till de tjänstgöringar som är kopplade till person ID
    
    *     @var string $startDate.onOrBefore Begränsa urvalet till tjänstgöringar som har ett startDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $startDate.onOrAfter Begränsa urvalet till tjänstgöringar som har ett startDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $endDate.onOrBefore Begränsa urvalet till tjänstgöringar som har ett endDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $endDate.onOrAfter Begränsa urvalet till tjänstgöringar som har ett endDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var array $expand Beskriver om expanderade data ska hämtas
    *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
    *     @var string $sortkey Anger hur resultatet ska sorteras.
    *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
    
    *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
    
    * }
    * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDutyBadRequestException
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDutyForbiddenException
    *
    * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duties|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
    */
    public function getDuty(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetDuty($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta tjänstgöringar en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många tjänstgöringar på en gång genom att skicka ett anrop med en lista med önskade tjänstgöringar.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostDutiesLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostDutiesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyExpanded[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postDutiesLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostDutiesLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för tjänstgöringen som ska hämtas
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDutyByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDutyByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDutyByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getDutyById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetDutyById($id, $queryParameters), $fetch);
    }
    /**
    * 
    *
    * @param array $queryParameters {
    *     @var array $groupType Begränsa urvalet till grupper av en eller flera type.
    
    *     @var array $schoolTypes Begränsa urvalet av grupper till de som är har en av de angivna skolformerna.
    
    *     @var array $organisation Begränsa urvalet till de grupper som direkt kopplade till angivna organisationselement.
    
    *     @var string $startDate.onOrBefore Begränsa urvalet till grupper som har ett startDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $startDate.onOrAfter Begränsa urvalet till grupper som har ett startDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $endDate.onOrBefore Begränsa urvalet till grupper som har ett endDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $endDate.onOrAfter Begränsa urvalet till grupper som har ett endDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var array $expand Beskriver om expanderade data ska hämtas
    *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
    *     @var string $sortkey Anger hur resultatet ska sorteras.
    *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
    
    *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
    
    * }
    * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupBadRequestException
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupForbiddenException
    *
    * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupsExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
    */
    public function getGroup(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetGroup($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta grupper en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många grupper på en gång genom att skicka ett anrop med en lista med önskade grupper.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostGroupsLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostGroupsLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpanded[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postGroupsLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostGroupsLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för grupp som ska hämtas
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getGroupById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetGroupById($id, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var array $schoolType Begränsa urvalet till de program som matchar skolformen.
     *     @var string $code Begränsta urvalet till de program som matchar programkod
     *     @var string $parentProgramme Begränsta urvalet till de program som matchar angivet parentProgramme.
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var string $sortkey Anger hur resultatet ska sorteras.
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetProgrammeBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetProgrammeForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Programmes|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getProgramme(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetProgramme($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta program en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många program på en gång genom att skicka ett anrop med en lista med önskade program.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostProgrammesLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostProgrammesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Programme[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postProgrammesLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostProgrammesLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för program som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetProgrammeByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetProgrammeByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetProgrammeByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Programme|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getProgrammeById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetProgrammeById($id, $queryParameters), $fetch);
    }
    /**
    * 
    *
    * @param array $queryParameters {
    *     @var array $student Begränsa urvalet till utpekade elever.
    *     @var string $startDate.onOrBefore Begränsa urvalet till studieplaner som har ett startDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med. 
    
    *     @var string $startDate.onOrAfter Begränsa urvalet till studieplaner som har ett startDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med. 
    
    *     @var string $endDate.onOrBefore Begränsa urvalet till studieplaner som har ett endDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med. 
    
    *     @var string $endDate.onOrAfter Begränsa urvalet till studieplaner som har ett endDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med. 
    
    *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
    *     @var string $sortkey Anger hur resultatet ska sorteras.
    *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
    
    *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
    
    * }
    * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetStudyplanBadRequestException
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetStudyplanForbiddenException
    *
    * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlans|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
    */
    public function getStudyplan(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetStudyplan($queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för studieplan som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetStudyplanByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetStudyplanByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetStudyplanByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlan|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getStudyplanById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetStudyplanById($id, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var string $sortkey Anger hur resultatet ska sorteras.
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabuses|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getSyllabus(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetSyllabus($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta syllabuses en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många syllabuses på en gång genom att skicka ett anrop med en lista med önskade syllabuses.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostSyllabusesLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostSyllabusesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postSyllabusesLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostSyllabusesLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för syllabus som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getSyllabusById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetSyllabusById($id, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $organisation Begränsa urvalet till ett visst organisationslement (offeredAt).
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var string $sortkey 
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSchoolUnitOfferingBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSchoolUnitOfferingForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitOfferings|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getSchoolUnitOffering(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetSchoolUnitOffering($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta program en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många program på en gång genom att skicka ett anrop med en lista med önskade program.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostSchoolUnitOfferingsLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostSchoolUnitOfferingsLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitOffering[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postSchoolUnitOfferingsLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostSchoolUnitOfferingsLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för resursen som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSchoolUnitOfferingByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSchoolUnitOfferingByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSchoolUnitOfferingByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitOffering|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getSchoolUnitOfferingById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetSchoolUnitOfferingById($id, $queryParameters), $fetch);
    }
    /**
    * 
    *
    * @param array $queryParameters {
    *     @var string $member Begränsa urvalet till aktiviteter vars grupper `groups` inkluderar denna person. Tidsbegräninsgar (`startDate`, `endDate`) appliceras inte för detta filter.
    *     @var string $teacher Begränsa urvalet till aktiviteter vars lärare `teachers` inkluderar detta id i attributet `duty.id`. Tidsbegräninsgar (`startDate`, `endDate`) appliceras inte för detta filter.
    *     @var string $organisation Begränsa urvalet till utpekat organisationselement och dess underliggande element.
    *     @var string $group Begränsa urvalet till utpekad grupp.
    *     @var string $startDate.onOrBefore Begränsa urvalet till aktiviteter som har ett startDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $startDate.onOrAfter Begränsa urvalet till aktiviteter som har ett startDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $endDate.onOrBefore Begränsa urvalet till aktiviteter som har ett endDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $endDate.onOrAfter Begränsa urvalet till aktiviteter som har ett endDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var array $expand Beskriver om expanderade data ska hämtas
    *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
    *     @var string $sortkey 
    *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
    
    *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
    
    * }
    * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetActivityBadRequestException
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetActivityForbiddenException
    *
    * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activities|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
    */
    public function getActivity(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetActivity($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta aktiviteter en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många aktiviteter på en gång genom att skicka ett anrop med en lista med önskade aktiviteter.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivitiesLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostActivitiesLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostActivitiesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpanded[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postActivitiesLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivitiesLookupPostRequest $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostActivitiesLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för aktivitet som ska hämtas
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetActivityByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetActivityByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetActivityByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getActivityById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetActivityById($id, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $startTime.onOrAfter Hämta kalenderhändelser från och med denna tidpunkt (RFC 3339 format, t.ex. "2016-10-15T09:00:00+02:00").
     *     @var string $startTime.onOrBefore Hämta kalenderhändelser till och med denna tidpunkt (RFC 3339 format, t.ex. "2016-10-15T09:00:00+02:00").
     *     @var string $endTime.onOrBefore Hämta kalenderhändelser till och med denna tidpunkt (RFC 3339 format, t.ex. "2016-10-15T11:00:00+02:00").
     *     @var string $endTime.onOrAfter Hämta kalenderhändelser från och med denna tidpunkt (RFC 3339 format, t.ex. "2016-10-15T11:00:00+02:00").
     *     @var string $activity Begränsa urvalet till utpekad aktivitet.
     *     @var string $student Begränsa urvalet till kalenderhändelser vars aktivitet `activity.group` => `group.groupMemberships.person.id` eller `studentExceptions.student.id` inkluderar denna person. Tidsbegräninsgar (`startDate`, `endDate`) appliceras inte för detta filter.
     *     @var string $teacher Begränsa urvalet till kalenderhändelser vars aktiviteter `activity.teachers.duty.id` samt `teacherExceptions.duty.id` inkluderar denna tjänst `duty.id`. Tidsbegräninsgar (`startDate`, `endDate`) appliceras inte för detta filter.
     *     @var string $organisation Begränsa urvalet till utpekat organisationselement och dess underliggande element.
     *     @var string $group Begränsa urvalet till utpekad grupp relaterat genom kopplade aktiviteter.
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var array $expand Beskriver om expanderade data ska hämtas för aktiviteten.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var string $sortkey Anger hur resultatet ska sorteras.
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvents|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getCalendarEvent(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetCalendarEvent($queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id Hämta en kalenderhändelse.
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas för aktiviteten.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvent|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getCalendarEventById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetCalendarEventById($id, $queryParameters), $fetch);
    }
    /**
     * Istället för att hämta kalenderhändelser en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många kalenderhändelser på en gång genom att skicka ett anrop med en lista med önskade kalenderhändelser.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventsLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostCalendarEventsLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postCalendarEventsLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventsLookupPostRequest $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostCalendarEventsLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $student Begränsa urvalet till utpekad person.
     *     @var string $organisation Begränsa urvalet till utpekat organisationselement och dess underliggande element.
     *     @var string $calendarEvent Begränsa urvalet till utpekad kalenderpost.
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendances|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getAttendance(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAttendance($queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postAttendance(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostAttendance($requestBody), $fetch);
    }
    /**
     * Istället för att hämta aktiviteter en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många aktiviteter på en gång genom att skicka ett anrop med en lista med önskade aktiviteter.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendancesLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAttendancesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postAttendancesLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendancesLookupPostRequest $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostAttendancesLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för närvaro posten som ska tas bort.
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function deleteAttendanceById(string $id, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\DeleteAttendanceById($id), $fetch);
    }
    /**
     * 
     *
     * @param string $id Hämta en Attendance post.
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getAttendanceById(string $id, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAttendanceById($id), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var array $group Begränsa urvalet till utpekade gruppers ID.
     *     @var string $person Begränsa urvalet till utpekad person.
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var array $expand Beskriver om expanderade data ska hämtas för aktiviteten.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceEventBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceEventForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvents|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getAttendanceEvent(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAttendanceEvent($queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvent $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAttendanceEventForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvent|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postAttendanceEvent(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvent $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostAttendanceEvent($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för närvaro posten som ska tas bort.
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceEventByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceEventByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceEventByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function deleteAttendanceEventById(string $id, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\DeleteAttendanceEventById($id), $fetch);
    }
    /**
     * 
     *
     * @param string $id Hämta en närvarohändelse.
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas för aktiviteten.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceEventByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceEventByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceEventByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvent|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getAttendanceEventById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAttendanceEventById($id, $queryParameters), $fetch);
    }
    /**
     * Istället för att hämta närvarohändelse en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många närvarohändelser på en gång genom att skicka ett anrop med en lista med önskade närvarohändelser.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEventsLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas för aktiviteten.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAttendanceEventsLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAttendanceEventsLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvent[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postAttendanceEventsLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEventsLookupPostRequest $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostAttendanceEventsLookup($requestBody, $queryParameters), $fetch);
    }
    /**
    * 
    *
    * @param array $queryParameters {
    *     @var string $placement Begränsa urvalet till scheman för utpekad placering.
    *     @var string $group Begränsa urvalet till scheman vars placeringar är kopplad till utpekad grupp.
    *     @var string $startDate.onOrBefore Begränsa urvalet till vistelseschema som har ett startDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $startDate.onOrAfter Begränsa urvalet till vistelseschema som har ett startDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15").
    
    *     @var string $endDate.onOrBefore Begränsa urvalet till vistelseschema som har ett endDate värde innan eller på det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $endDate.onOrAfter Begränsa urvalet till vistelseschema som har ett endDate värde på eller efter det angivna datumet (RFC 3339-format, t.ex. "2016-10-15"). 
    Poster med ett endDate som ej är satt, tas alltid med.
    
    *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
    
    *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
    
    *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
    *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
    
    *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
    
    * }
    * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceScheduleBadRequestException
    * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceScheduleForbiddenException
    *
    * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedules|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
    */
    public function getAttendanceSchedule(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAttendanceSchedule($queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postAttendanceSchedule(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostAttendanceSchedule($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för vistelseschema som ska tas bort.
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceScheduleByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceScheduleByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteAttendanceScheduleByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function deleteAttendanceScheduleById(string $id, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\DeleteAttendanceScheduleById($id), $fetch);
    }
    /**
     * 
     *
     * @param string $id Id för vistelseschema att hämta.
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceScheduleByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceScheduleByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAttendanceScheduleByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getAttendanceScheduleById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAttendanceScheduleById($id, $queryParameters), $fetch);
    }
    /**
     * Istället för att hämta vistelsescheman ett i taget med en loop av GET-anrop så finns det även möjlighet att hämta många vistelsescheman på en gång genom att skicka ett anrop med en lista med önskade vistelsescheman.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAttendanceScheduleLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAttendanceScheduleLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpanded[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postAttendanceScheduleLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleLookupPostRequest $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostAttendanceScheduleLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $organisation Begränsa urvalet till de betyg som är kopplade till skolenhet.
     *     @var string $student Begränsa urvalet till de betyg som tillhör eleven
     *     @var string $registeredBy Begränsa urvalet till de betyg som är registrerade av personen
     *     @var string $gradingTeacher Begränsa urvalet till de betyg som är utfärdade av ansvarig lärare
     *     @var string $registeredDate.onOrAfter Begränsa urvalet av betyg till de som är registerade inom det intervall som startar på angivet datum (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *     @var string $registeredDate.onOrBefore Begränsa urvalet av betyg till de som är registerade inom det intervall som slutar på angivet datum (RFC 3339-format, t.ex. "2016-10-15"). Inkluderande.
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var string $sortkey Anger hur resultatet ska sorteras.
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGradeBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGradeForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grades|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getGrade(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetGrade($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta betyg ett i taget med en loop a GET-anrop så finns det även möjlighet att hämta många betyg på en gång genom att skicka ett anrop med en lista med önskade objekt.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostGradesLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostGradesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grade[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postGradesLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostGradesLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för betyg som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGradeByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGradeByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGradeByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grade|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getGradeById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetGradeById($id, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $organisation Begränsa urvalet till den frånvaro/ledighet som är kopplad till organisationen.
     *     @var string $student Begränsa urvalet till den frånvaro/ledighet som är kopplad till eleven
     *     @var string $registeredBy Begränsa urvalet till den frånvaro/ledighet som är registrerad av personen
     *     @var string $type Begränsa urvalet till den frånvaro/ledighet som är av angiven typ
     *     @var string $startTime.onOrBefore Endast anmälda frånvaro som startar innan eller på denna tidpunkt (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $startTime.onOrAfter Endast anmälda frånvaro/ledighet som startar efter denna tidpunkt (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $endTime.onOrBefore Endast anmälda frånvaro som slutar innan eller på denna tidpunkt (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $endTime.onOrAfter Endast anmälda frånvaro/ledighet som slutar efter denna tidpunkt (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var string $sortkey Anger hur resultatet ska sorteras.
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absences|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getAbsence(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAbsence($queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absence $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAbsenceForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absence|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postAbsence(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absence $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostAbsence($requestBody), $fetch);
    }
    /**
     * Istället för att hämta anmälda frånvaro en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många anmälda frånvaroposter på en gång genom att skicka ett anrop med en lista med önskade objekt.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAbsencesLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostAbsencesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absence[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postAbsencesLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostAbsencesLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för anmäld frånvaro som ska hämtas
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absence|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getAbsenceById(string $id, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAbsenceById($id), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $startDate Hämta aggregerad närvaro från och med detta datum (RFC 3339-format, e.g. "2016-10-15")
     *     @var string $endDate Hämta aggregerad närvaro till och med detta datum (RFC 3339-format, e.g. "2016-10-15")
     *     @var string $organisation Inkludera endast närvaro från aktiviteter vilka ägs av angivet organisationselement.
     *     @var array $schoolType Hämta endast närvaro information från aktiviteter vilka är kopplade mot angiven skolform.
     *     @var array $student Filtrera på elev (person).
     *     @var array $expand Beskriver om och vilken expanderade data som returneras i samband med närvaroinformationen.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAggregatedAttendanceBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAggregatedAttendanceForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendances|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getAggregatedAttendance(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetAggregatedAttendance($queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $organisation Begränsa urvalet till ett visst organisationselemet (owner).
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var string $sortkey 
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetResourceBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetResourceForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Resources|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getResource(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetResource($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta resurser en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många resurser på en gång genom att skicka ett anrop med en lista med önskade resurser.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostResourcesLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostResourcesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Resource[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postResourcesLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostResourcesLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för resursen som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetResourceByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetResourceByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetResourceByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Resource|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getResourceById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetResourceById($id, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $organisation Begränsa urvalet till ett visst organisationselemet (owner).
     *     @var string $meta.created.before Endast poster skapade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.created.after Endast poster skapade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var string $meta.modified.before Endast poster modifierade på eller före detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Inkluderande.
     *     @var string $meta.modified.after Endast poster modifierade efter detta timestamp (RFC 3339 format, tex "2015-12-12T10:30:00+01:00"). Exkluderande.
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     *     @var string $sortkey 
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetRoomBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetRoomForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Rooms|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getRoom(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetRoom($queryParameters), $fetch);
    }
    /**
     * Istället för att hämta salar en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många salar på en gång genom att skicka ett anrop med en lista med önskade salar.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody 
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostRoomsLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostRoomsLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Room[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postRoomsLookup(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup $requestBody, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostRoomsLookup($requestBody, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för salen som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetRoomByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetRoomByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetRoomByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Room|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getRoomById(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetRoomById($id, $queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSubscriptionForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Subscriptions|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getSubscription(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetSubscription($queryParameters), $fetch);
    }
    /**
     * 
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CreateSubscription $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostSubscriptionForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Subscription|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postSubscription(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CreateSubscription $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostSubscription($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för prenumerationen som ska tas bort
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteSubscriptionByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteSubscriptionByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\DeleteSubscriptionByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function deleteSubscriptionById(string $id, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\DeleteSubscriptionById($id), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för prenumerationen som ska hämtas
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSubscriptionByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSubscriptionByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSubscriptionByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Subscription|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getSubscriptionById(string $id, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetSubscriptionById($id), $fetch);
    }
    /**
     * 
     *
     * @param string $id ID för prenumerationen som ska uppdateras
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PatchSubscriptionByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PatchSubscriptionByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PatchSubscriptionByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Subscription|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function patchSubscriptionById(string $id, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PatchSubscriptionById($id), $fetch);
    }
    /**
     * 
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\LogEntry $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostLogForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postLog(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\LogEntry $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostLog($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StatisticsEntry $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostStatisticsForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function postStatistics(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StatisticsEntry $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\PostStatistics($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $after Hämta borttag som inträffat efter specificerad tidpunkt (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *     @var array $entities En lista med de entitetstyper vars borttag ska hämtas
     *     @var int $limit Antal poster som ska visas i resultatet. Utelämnas det så returnas så många poster som möjligt av servern, se `pageToken`.
     *     @var string $pageToken Ett opakt värde som servern givit som svar på en tidigare ställd fråga. Kan inte komibineras med andra filter men väl med `limit`.
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDeletedEntityBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDeletedEntityForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntities|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error|\Psr\Http\Message\ResponseInterface
     */
    public function getDeletedEntity(array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint\GetDeletedEntity($queryParameters), $fetch);
    }
    public static function create($httpClient = null, array $additionalPlugins = [], array $additionalNormalizers = [])
    {
        if (null === $httpClient) {
            $httpClient = \Http\Discovery\Psr18ClientDiscovery::find();
            $plugins = [];
            $uri = \Http\Discovery\Psr17FactoryDiscovery::findUriFactory()->createUri('https://ssr.loc/api/v2.0');
            $plugins[] = new \Http\Client\Common\Plugin\AddHostPlugin($uri);
            $plugins[] = new \Http\Client\Common\Plugin\AddPathPlugin($uri);
            if (count($additionalPlugins) > 0) {
                $plugins = array_merge($plugins, $additionalPlugins);
            }
            $httpClient = new \Http\Client\Common\PluginClient($httpClient, $plugins);
        }
        $requestFactory = \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory();
        $normalizers = [new \Symfony\Component\Serializer\Normalizer\ArrayDenormalizer(), new \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\JaneObjectNormalizer()];
        if (count($additionalNormalizers) > 0) {
            $normalizers = array_merge($normalizers, $additionalNormalizers);
        }
        $serializer = new \Symfony\Component\Serializer\Serializer($normalizers, [new \Symfony\Component\Serializer\Encoder\JsonEncoder(new \Symfony\Component\Serializer\Encoder\JsonEncode(), new \Symfony\Component\Serializer\Encoder\JsonDecode(['json_decode_associative' => true]))]);
        return new static($httpClient, $requestFactory, $serializer, $streamFactory);
    }
}