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
class CalendarEventsLookupPostRequestNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventsLookupPostRequest::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventsLookupPostRequest::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventsLookupPostRequest();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('ids', $data)) {
            $values = [];
            foreach ($data['ids'] as $value) {
                $values[] = $value;
            }
            $object->setIds($values);
            unset($data['ids']);
        }
        if (\array_key_exists('activities', $data)) {
            $values_1 = [];
            foreach ($data['activities'] as $value_1) {
                $values_1[] = $value_1;
            }
            $object->setActivities($values_1);
            unset($data['activities']);
        }
        if (\array_key_exists('student', $data)) {
            $values_2 = [];
            foreach ($data['student'] as $value_2) {
                $values_2[] = $value_2;
            }
            $object->setStudent($values_2);
            unset($data['student']);
        }
        if (\array_key_exists('teacher', $data)) {
            $values_3 = [];
            foreach ($data['teacher'] as $value_3) {
                $values_3[] = $value_3;
            }
            $object->setTeacher($values_3);
            unset($data['teacher']);
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
        if ($data->isInitialized('ids') && null !== $data->getIds()) {
            $values = [];
            foreach ($data->getIds() as $value) {
                $values[] = $value;
            }
            $dataArray['ids'] = $values;
        }
        if ($data->isInitialized('activities') && null !== $data->getActivities()) {
            $values_1 = [];
            foreach ($data->getActivities() as $value_1) {
                $values_1[] = $value_1;
            }
            $dataArray['activities'] = $values_1;
        }
        if ($data->isInitialized('student') && null !== $data->getStudent()) {
            $values_2 = [];
            foreach ($data->getStudent() as $value_2) {
                $values_2[] = $value_2;
            }
            $dataArray['student'] = $values_2;
        }
        if ($data->isInitialized('teacher') && null !== $data->getTeacher()) {
            $values_3 = [];
            foreach ($data->getTeacher() as $value_3) {
                $values_3[] = $value_3;
            }
            $dataArray['teacher'] = $values_3;
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventsLookupPostRequest::class => false];
    }
}