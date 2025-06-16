<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class GetSyllabusById extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
    protected $id;
    /**
     * 
     *
     * @param string $id ID för syllabus som ska hämtas
     * @param array $queryParameters {
     *     @var bool $expandReferenceNames Returnera `displayName` för alla refererade objekt.
     * }
     */
    public function __construct(string $id, array $queryParameters = [])
    {
        $this->id = $id;
        $this->queryParameters = $queryParameters;
    }
    use \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\EndpointTrait;
    public function getMethod(): string
    {
        return 'GET';
    }
    public function getUri(): string
    {
        return str_replace(['{id}'], [$this->id], '/syllabuses/{id}');
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
        $optionsResolver->setDefined(['expandReferenceNames']);
        $optionsResolver->setRequired([]);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('expandReferenceNames', ['bool']);
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdForbiddenException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdNotFoundException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus', 'json');
        }
        if (is_null($contentType) === false && (400 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdBadRequestException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (is_null($contentType) === false && (403 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdForbiddenException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (404 === $status) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetSyllabusByIdNotFoundException($response);
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