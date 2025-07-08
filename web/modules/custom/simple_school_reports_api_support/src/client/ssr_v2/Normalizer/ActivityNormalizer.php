<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer;

use Jane\Component\JsonSchemaRuntime\Reference;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Normalizer\CheckArray;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Normalizer\ValidatorTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
class ActivityNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activity::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activity::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activity();
        if (\array_key_exists('calendarEventsRequired', $data) && \is_int($data['calendarEventsRequired'])) {
            $data['calendarEventsRequired'] = (bool) $data['calendarEventsRequired'];
        }
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('id', $data)) {
            $object->setId($data['id']);
            unset($data['id']);
        }
        if (\array_key_exists('meta', $data)) {
            $object->setMeta($this->denormalizer->denormalize($data['meta'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Meta::class, 'json', $context));
            unset($data['meta']);
        }
        if (\array_key_exists('displayName', $data)) {
            $object->setDisplayName($data['displayName']);
            unset($data['displayName']);
        }
        if (\array_key_exists('calendarEventsRequired', $data)) {
            $object->setCalendarEventsRequired($data['calendarEventsRequired']);
            unset($data['calendarEventsRequired']);
        }
        if (\array_key_exists('startDate', $data)) {
            $object->setStartDate(\DateTime::createFromFormat('Y-m-d', $data['startDate'])->setTime(0, 0, 0));
            unset($data['startDate']);
        }
        if (\array_key_exists('endDate', $data)) {
            $object->setEndDate(\DateTime::createFromFormat('Y-m-d', $data['endDate'])->setTime(0, 0, 0));
            unset($data['endDate']);
        }
        if (\array_key_exists('activityType', $data)) {
            $object->setActivityType($data['activityType']);
            unset($data['activityType']);
        }
        if (\array_key_exists('comment', $data)) {
            $object->setComment($data['comment']);
            unset($data['comment']);
        }
        if (\array_key_exists('minutesPlanned', $data)) {
            $object->setMinutesPlanned($data['minutesPlanned']);
            unset($data['minutesPlanned']);
        }
        if (\array_key_exists('groups', $data)) {
            $values = [];
            foreach ($data['groups'] as $value) {
                $values[] = $this->denormalizer->denormalize($value, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupReference::class, 'json', $context);
            }
            $object->setGroups($values);
            unset($data['groups']);
        }
        if (\array_key_exists('teachers', $data)) {
            $values_1 = [];
            foreach ($data['teachers'] as $value_1) {
                $values_1[] = $this->denormalizer->denormalize($value_1, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyAssignment::class, 'json', $context);
            }
            $object->setTeachers($values_1);
            unset($data['teachers']);
        }
        if (\array_key_exists('syllabus', $data)) {
            $object->setSyllabus($data['syllabus']);
            unset($data['syllabus']);
        }
        if (\array_key_exists('organisation', $data)) {
            $object->setOrganisation($data['organisation']);
            unset($data['organisation']);
        }
        if (\array_key_exists('parentActivity', $data)) {
            $object->setParentActivity($data['parentActivity']);
            unset($data['parentActivity']);
        }
        foreach ($data as $key => $value_2) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value_2;
            }
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['id'] = $data->getId();
        $dataArray['displayName'] = $data->getDisplayName();
        $dataArray['calendarEventsRequired'] = $data->getCalendarEventsRequired();
        $dataArray['startDate'] = $data->getStartDate()?->format('Y-m-d');
        if ($data->isInitialized('endDate') && null !== $data->getEndDate()) {
            $dataArray['endDate'] = $data->getEndDate()?->format('Y-m-d');
        }
        if ($data->isInitialized('activityType') && null !== $data->getActivityType()) {
            $dataArray['activityType'] = $data->getActivityType();
        }
        if ($data->isInitialized('comment') && null !== $data->getComment()) {
            $dataArray['comment'] = $data->getComment();
        }
        if ($data->isInitialized('minutesPlanned') && null !== $data->getMinutesPlanned()) {
            $dataArray['minutesPlanned'] = $data->getMinutesPlanned();
        }
        $values = [];
        foreach ($data->getGroups() as $value) {
            $values[] = $this->normalizer->normalize($value, 'json', $context);
        }
        $dataArray['groups'] = $values;
        if ($data->isInitialized('teachers') && null !== $data->getTeachers()) {
            $values_1 = [];
            foreach ($data->getTeachers() as $value_1) {
                $values_1[] = $this->normalizer->normalize($value_1, 'json', $context);
            }
            $dataArray['teachers'] = $values_1;
        }
        if ($data->isInitialized('syllabus') && null !== $data->getSyllabus()) {
            $dataArray['syllabus'] = $data->getSyllabus();
        }
        $dataArray['organisation'] = $data->getOrganisation();
        if ($data->isInitialized('parentActivity') && null !== $data->getParentActivity()) {
            $dataArray['parentActivity'] = $data->getParentActivity();
        }
        foreach ($data as $key => $value_2) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value_2;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activity::class => false];
    }
}