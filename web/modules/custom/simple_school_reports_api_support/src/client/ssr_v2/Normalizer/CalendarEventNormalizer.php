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
class CalendarEventNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvent::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvent::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvent();
        if (\array_key_exists('cancelled', $data) && \is_int($data['cancelled'])) {
            $data['cancelled'] = (bool) $data['cancelled'];
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
        if (\array_key_exists('activity', $data)) {
            $object->setActivity($data['activity']);
            unset($data['activity']);
        }
        if (\array_key_exists('startTime', $data)) {
            $object->setStartTime(\DateTime::createFromFormat('Y-m-d\TH:i:sP', $data['startTime']));
            unset($data['startTime']);
        }
        if (\array_key_exists('endTime', $data)) {
            $object->setEndTime(\DateTime::createFromFormat('Y-m-d\TH:i:sP', $data['endTime']));
            unset($data['endTime']);
        }
        if (\array_key_exists('cancelled', $data)) {
            $object->setCancelled($data['cancelled']);
            unset($data['cancelled']);
        }
        if (\array_key_exists('teachingLengthTeacher', $data)) {
            $object->setTeachingLengthTeacher($data['teachingLengthTeacher']);
            unset($data['teachingLengthTeacher']);
        }
        if (\array_key_exists('teachingLengthStudent', $data)) {
            $object->setTeachingLengthStudent($data['teachingLengthStudent']);
            unset($data['teachingLengthStudent']);
        }
        if (\array_key_exists('comment', $data)) {
            $object->setComment($data['comment']);
            unset($data['comment']);
        }
        if (\array_key_exists('studentExceptions', $data)) {
            $values = [];
            foreach ($data['studentExceptions'] as $value) {
                $values[] = $this->denormalizer->denormalize($value, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudentException::class, 'json', $context);
            }
            $object->setStudentExceptions($values);
            unset($data['studentExceptions']);
        }
        if (\array_key_exists('teacherExceptions', $data)) {
            $values_1 = [];
            foreach ($data['teacherExceptions'] as $value_1) {
                $values_1[] = $this->denormalizer->denormalize($value_1, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\TeacherException::class, 'json', $context);
            }
            $object->setTeacherExceptions($values_1);
            unset($data['teacherExceptions']);
        }
        if (\array_key_exists('rooms', $data)) {
            $values_2 = [];
            foreach ($data['rooms'] as $value_2) {
                $values_2[] = $this->denormalizer->denormalize($value_2, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventRoomsInner::class, 'json', $context);
            }
            $object->setRooms($values_2);
            unset($data['rooms']);
        }
        if (\array_key_exists('resources', $data)) {
            $values_3 = [];
            foreach ($data['resources'] as $value_3) {
                $values_3[] = $this->denormalizer->denormalize($value_3, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventResourcesInner::class, 'json', $context);
            }
            $object->setResources($values_3);
            unset($data['resources']);
        }
        if (\array_key_exists('_embedded', $data)) {
            $object->setEmbedded($this->denormalizer->denormalize($data['_embedded'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventEmbedded::class, 'json', $context));
            unset($data['_embedded']);
        }
        foreach ($data as $key => $value_4) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value_4;
            }
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['id'] = $data->getId();
        $dataArray['activity'] = $data->getActivity();
        $dataArray['startTime'] = $data->getStartTime()?->format('Y-m-d\TH:i:sP');
        $dataArray['endTime'] = $data->getEndTime()?->format('Y-m-d\TH:i:sP');
        if ($data->isInitialized('cancelled') && null !== $data->getCancelled()) {
            $dataArray['cancelled'] = $data->getCancelled();
        }
        if ($data->isInitialized('teachingLengthTeacher') && null !== $data->getTeachingLengthTeacher()) {
            $dataArray['teachingLengthTeacher'] = $data->getTeachingLengthTeacher();
        }
        if ($data->isInitialized('teachingLengthStudent') && null !== $data->getTeachingLengthStudent()) {
            $dataArray['teachingLengthStudent'] = $data->getTeachingLengthStudent();
        }
        if ($data->isInitialized('comment') && null !== $data->getComment()) {
            $dataArray['comment'] = $data->getComment();
        }
        if ($data->isInitialized('studentExceptions') && null !== $data->getStudentExceptions()) {
            $values = [];
            foreach ($data->getStudentExceptions() as $value) {
                $values[] = $this->normalizer->normalize($value, 'json', $context);
            }
            $dataArray['studentExceptions'] = $values;
        }
        if ($data->isInitialized('teacherExceptions') && null !== $data->getTeacherExceptions()) {
            $values_1 = [];
            foreach ($data->getTeacherExceptions() as $value_1) {
                $values_1[] = $this->normalizer->normalize($value_1, 'json', $context);
            }
            $dataArray['teacherExceptions'] = $values_1;
        }
        if ($data->isInitialized('rooms') && null !== $data->getRooms()) {
            $values_2 = [];
            foreach ($data->getRooms() as $value_2) {
                $values_2[] = $this->normalizer->normalize($value_2, 'json', $context);
            }
            $dataArray['rooms'] = $values_2;
        }
        if ($data->isInitialized('resources') && null !== $data->getResources()) {
            $values_3 = [];
            foreach ($data->getResources() as $value_3) {
                $values_3[] = $this->normalizer->normalize($value_3, 'json', $context);
            }
            $dataArray['resources'] = $values_3;
        }
        if ($data->isInitialized('embedded') && null !== $data->getEmbedded()) {
            $dataArray['_embedded'] = $this->normalizer->normalize($data->getEmbedded(), 'json', $context);
        }
        foreach ($data as $key => $value_4) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value_4;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvent::class => false];
    }
}