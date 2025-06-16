<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class GetGroup extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
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
        return '/groups';
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
        $optionsResolver->setDefined(['groupType', 'schoolTypes', 'organisation', 'startDate.onOrBefore', 'startDate.onOrAfter', 'endDate.onOrBefore', 'endDate.onOrAfter', 'meta.created.before', 'meta.created.after', 'meta.modified.before', 'meta.modified.after', 'expand', 'expandReferenceNames', 'sortkey', 'limit', 'pageToken']);
        $optionsResolver->setRequired([]);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('groupType', ['array']);
        $optionsResolver->addAllowedTypes('schoolTypes', ['array']);
        $optionsResolver->addAllowedTypes('organisation', ['array']);
        $optionsResolver->addAllowedTypes('startDate.onOrBefore', ['string']);
        $optionsResolver->addAllowedTypes('startDate.onOrAfter', ['string']);
        $optionsResolver->addAllowedTypes('endDate.onOrBefore', ['string']);
        $optionsResolver->addAllowedTypes('endDate.onOrAfter', ['string']);
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
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupsExpanded|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupsExpanded', 'json');
        }
        if (is_null($contentType) === false && (400 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupBadRequestException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (is_null($contentType) === false && (403 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetGroupForbiddenException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
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