<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class GetAbsence extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
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
        return '/absences';
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
        $optionsResolver->setDefined(['organisation', 'student', 'registeredBy', 'type', 'startTime.onOrBefore', 'startTime.onOrAfter', 'endTime.onOrBefore', 'endTime.onOrAfter', 'meta.created.before', 'meta.created.after', 'meta.modified.before', 'meta.modified.after', 'expandReferenceNames', 'sortkey', 'limit', 'pageToken']);
        $optionsResolver->setRequired([]);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('organisation', ['string']);
        $optionsResolver->addAllowedTypes('student', ['string']);
        $optionsResolver->addAllowedTypes('registeredBy', ['string']);
        $optionsResolver->addAllowedTypes('type', ['string']);
        $optionsResolver->addAllowedTypes('startTime.onOrBefore', ['string']);
        $optionsResolver->addAllowedTypes('startTime.onOrAfter', ['string']);
        $optionsResolver->addAllowedTypes('endTime.onOrBefore', ['string']);
        $optionsResolver->addAllowedTypes('endTime.onOrAfter', ['string']);
        $optionsResolver->addAllowedTypes('meta.created.before', ['string']);
        $optionsResolver->addAllowedTypes('meta.created.after', ['string']);
        $optionsResolver->addAllowedTypes('meta.modified.before', ['string']);
        $optionsResolver->addAllowedTypes('meta.modified.after', ['string']);
        $optionsResolver->addAllowedTypes('expandReferenceNames', ['bool']);
        $optionsResolver->addAllowedTypes('sortkey', ['string']);
        $optionsResolver->addAllowedTypes('limit', ['int']);
        $optionsResolver->addAllowedTypes('pageToken', ['string']);
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absences|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absences', 'json');
        }
        if (is_null($contentType) === false && (400 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceBadRequestException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (is_null($contentType) === false && (403 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetAbsenceForbiddenException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
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