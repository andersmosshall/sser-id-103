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
class AttendancesLookupPostRequestNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendancesLookupPostRequest::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendancesLookupPostRequest::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendancesLookupPostRequest();
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
        if (\array_key_exists('students', $data)) {
            $values_2 = [];
            foreach ($data['students'] as $value_2) {
                $values_2[] = $value_2;
            }
            $object->setStudents($values_2);
            unset($data['students']);
        }
        if (\array_key_exists('calendareEvents', $data)) {
            $values_3 = [];
            foreach ($data['calendareEvents'] as $value_3) {
                $values_3[] = $value_3;
            }
            $object->setCalendareEvents($values_3);
            unset($data['calendareEvents']);
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
        if ($data->isInitialized('students') && null !== $data->getStudents()) {
            $values_2 = [];
            foreach ($data->getStudents() as $value_2) {
                $values_2[] = $value_2;
            }
            $dataArray['students'] = $values_2;
        }
        if ($data->isInitialized('calendareEvents') && null !== $data->getCalendareEvents()) {
            $values_3 = [];
            foreach ($data->getCalendareEvents() as $value_3) {
                $values_3[] = $value_3;
            }
            $dataArray['calendareEvents'] = $values_3;
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendancesLookupPostRequest::class => false];
    }
}