<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class GetPerson extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
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
    */
    public function __construct(array $queryParameters = [])
    {
        $this->queryParameters = $queryParameters;
    }
    use \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\EndpointTrait;
    public function getMethod(): string
    {
        return 'GET';
    }
    public function getUri(): string
    {
        return '/persons';
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null): array
    {
        return [[], null];
    }
    public function getExtraHeaders(): array
    {
        return ['Accept' => ['application/json']];
    }
    protected function getQueryOptionsResolver(): \Symfony\Component\OptionsResolver\OptionsResolver
    {
        $optionsResolver = parent::getQueryOptionsResolver();
        $optionsResolver->setDefined(['nameContains', 'civicNo', 'eduPersonPrincipalName', 'identifier.value', 'identifier.context', 'relationship.entity.type', 'relationship.organisation', 'relationship.startDate.onOrBefore', 'relationship.startDate.onOrAfter', 'relationship.endDate.onOrBefore', 'relationship.endDate.onOrAfter', 'meta.created.before', 'meta.created.after', 'meta.modified.before', 'meta.modified.after', 'expand', 'expandReferenceNames', 'sortkey', 'limit', 'pageToken']);
        $optionsResolver->setRequired([]);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('nameContains', ['array']);
        $optionsResolver->addAllowedTypes('civicNo', ['string']);
        $optionsResolver->addAllowedTypes('eduPersonPrincipalName', ['string']);
        $optionsResolver->addAllowedTypes('identifier.value', ['string']);
        $optionsResolver->addAllowedTypes('identifier.context', ['string']);
        $optionsResolver->addAllowedTypes('relationship.entity.type', ['string']);
        $optionsResolver->addAllowedTypes('relationship.organisation', ['string']);
        $optionsResolver->addAllowedTypes('relationship.startDate.onOrBefore', ['string']);
        $optionsResolver->addAllowedTypes('relationship.startDate.onOrAfter', ['string']);
        $optionsResolver->addAllowedTypes('relationship.endDate.onOrBefore', ['string']);
        $optionsResolver->addAllowedTypes('relationship.endDate.onOrAfter', ['string']);
        $optionsResolver->addAllowedTypes('meta.created.before', ['string']);
        $optionsResolver->addAllowedTypes('meta.created.after', ['string']);
        $optionsResolver->addAllowedTypes('meta.modified.before', ['string']);
        $optionsResolver->addAllowedTypes('meta.modified.after', ['string']);
        $optionsResolver->addAllowedTypes('expand', ['array']);
        $optionsResolver->addAllowedTypes('expandReferenceNames', ['bool']);
        $optionsResolver->addAllowedTypes('sortkey', ['string']);
        $optionsResolver->addAllowedTypes('limit', ['int']);
        $optionsResolver->addAllowedTypes('pageToken', ['string']);
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsExpanded', 'json');
        }
        if (is_null($contentType) === false && (400 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonBadRequestException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (is_null($contentType) === false && (403 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetPersonForbiddenException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (mb_strpos($contentType, 'application/json') !== false) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json');
        }
    }
    public function getAuthenticationScopes(): array
    {
        return ['BearerAuth'];
    }
}