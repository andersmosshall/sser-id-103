<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class PostActivitiesLookup extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
    /**
     * Istället för att hämta aktiviteter en i taget med en loop av GET-anrop så finns det även möjlighet att hämta många aktiviteter på en gång genom att skicka ett anrop med en lista med önskade aktiviteter.
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivitiesLookupPostRequest $requestBody 
     * @param array $queryParameters {
     *     @var array $expand Beskriver om expanderade data ska hämtas
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     */
    public function __construct(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivitiesLookupPostRequest $requestBody, array $queryParameters = [])
    {
        $this->body = $requestBody;
        $this->queryParameters = $queryParameters;
    }
    use \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\EndpointTrait;
    public function getMethod(): string
    {
        return 'POST';
    }
    public function getUri(): string
    {
        return '/activities/lookup';
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null): array
    {
        if ($this->body instanceof \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivitiesLookupPostRequest) {
            return [['Content-Type' => ['application/json']], $serializer->serialize($this->body, 'json')];
        }
        return [[], null];
    }
    public function getExtraHeaders(): array
    {
        return ['Accept' => ['application/json']];
    }
    protected function getQueryOptionsResolver(): \Symfony\Component\OptionsResolver\OptionsResolver
    {
        $optionsResolver = parent::getQueryOptionsResolver();
        $optionsResolver->setDefined(['expand', 'expandReferenceNames']);
        $optionsResolver->setRequired([]);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('expand', ['array']);
        $optionsResolver->addAllowedTypes('expandReferenceNames', ['bool']);
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostActivitiesLookupForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostActivitiesLookupServiceUnavailableException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpanded[]|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpanded[]', 'json');
        }
        if (is_null($contentType) === false && (403 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostActivitiesLookupForbiddenException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (is_null($contentType) === false && (503 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\PostActivitiesLookupServiceUnavailableException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
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