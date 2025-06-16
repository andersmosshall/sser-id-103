<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class PostAttendance extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
    /**
     * 
     *
     * @param \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance $requestBody 
     */
    public function __construct(\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance $requestBody)
    {
        $this->body = $requestBody;
    }
    use \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\EndpointTrait;
    public function getMethod(): string
    {
        return 'POST';
    }
    public function getUri(): string
    {
        return '/attendances';
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null): array
    {
        if ($this->body instanceof \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance) {
            return [['Content-Type' => ['application/json']], $serializer->serialize($this->body, 'json')];
        }
        return [[], null];
    }
    public function getExtraHeaders(): array
    {
        return ['Accept' => ['application/json']];
    }
    /**
     * {@inheritdoc}
     *
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance', 'json');
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