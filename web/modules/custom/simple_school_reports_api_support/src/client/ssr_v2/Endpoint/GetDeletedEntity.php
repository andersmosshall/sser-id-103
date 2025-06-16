<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class GetDeletedEntity extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $after Hämta borttag som inträffat efter specificerad tidpunkt (RFC 3339 format, tex "2015-12-12T10:30:00+01:00").
     *     @var array $entities En lista med de entitetstyper vars borttag ska hämtas
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
        return '/deletedEntities';
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
        $optionsResolver->setDefined(['after', 'entities', 'limit', 'pageToken']);
        $optionsResolver->setRequired([]);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('after', ['string']);
        $optionsResolver->addAllowedTypes('entities', ['array']);
        $optionsResolver->addAllowedTypes('limit', ['int']);
        $optionsResolver->addAllowedTypes('pageToken', ['string']);
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDeletedEntityBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDeletedEntityForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntities|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntities', 'json');
        }
        if (is_null($contentType) === false && (400 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDeletedEntityBadRequestException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (is_null($contentType) === false && (403 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetDeletedEntityForbiddenException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
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