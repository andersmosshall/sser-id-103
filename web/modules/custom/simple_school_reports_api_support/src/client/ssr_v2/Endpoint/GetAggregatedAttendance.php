<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class GetAggregatedAttendance extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
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
        return '/aggregatedAttendance';
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
        $optionsResolver->setDefined(['startDate', 'endDate', 'organisation', 'schoolType', 'student', 'expand', 'expandReferenceNames', 'limit', 'pageToken']);
        $optionsResolver->setRequired(['startDate', 'endDate']);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('startDate', ['string']);
        $optionsResolver->addAllowedTypes('endDate', ['string']);
        $optionsResolver->addAllowedTypes('organisation', ['string']);
        $optionsResolver->addAllowedTypes('schoolType', ['array']);
        $optionsResolver->addAllowedTypes('student', ['array']);
        $optionsResolver->addAllowedTypes('expand', ['array']);
        $optionsResolver->addAllowedTypes('expandReferenceNames', ['bool']);
        $optionsResolver->addAllowedTypes('limit', ['int']);
        $optionsResolver->addAllowedTypes('pageToken', ['string']);
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAggregatedAttendanceBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAggregatedAttendanceForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendances|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendances', 'json');
        }
        if (is_null($contentType) === false && (400 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAggregatedAttendanceBadRequestException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (is_null($contentType) === false && (403 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAggregatedAttendanceForbiddenException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
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