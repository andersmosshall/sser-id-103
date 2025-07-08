<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Endpoint;

class GetCalendarEvent extends \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\BaseEndpoint implements \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Client\Endpoint
{
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
        return '/calendarEvents';
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
        $optionsResolver->setDefined(['startTime.onOrAfter', 'startTime.onOrBefore', 'endTime.onOrBefore', 'endTime.onOrAfter', 'activity', 'student', 'teacher', 'organisation', 'group', 'meta.created.before', 'meta.created.after', 'meta.modified.before', 'meta.modified.after', 'expand', 'expandReferenceNames', 'sortkey', 'limit', 'pageToken']);
        $optionsResolver->setRequired(['startTime.onOrAfter', 'startTime.onOrBefore']);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('startTime.onOrAfter', ['string']);
        $optionsResolver->addAllowedTypes('startTime.onOrBefore', ['string']);
        $optionsResolver->addAllowedTypes('endTime.onOrBefore', ['string']);
        $optionsResolver->addAllowedTypes('endTime.onOrAfter', ['string']);
        $optionsResolver->addAllowedTypes('activity', ['string']);
        $optionsResolver->addAllowedTypes('student', ['string']);
        $optionsResolver->addAllowedTypes('teacher', ['string']);
        $optionsResolver->addAllowedTypes('organisation', ['string']);
        $optionsResolver->addAllowedTypes('group', ['string']);
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
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventBadRequestException
     * @throws \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventForbiddenException
     *
     * @return null|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvents|\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvents', 'json');
        }
        if (is_null($contentType) === false && (400 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventBadRequestException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
        }
        if (is_null($contentType) === false && (403 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            throw new \Drupal\simple_school_reports_api_support\client\ssr_v2\Exception\GetCalendarEventForbiddenException($serializer->deserialize($body, 'Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error', 'json'), $response);
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