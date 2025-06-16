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
class AttendanceScheduleNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule();
        if (\array_key_exists('temporary', $data) && \is_int($data['temporary'])) {
            $data['temporary'] = (bool) $data['temporary'];
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
        if (\array_key_exists('placement', $data)) {
            $object->setPlacement($data['placement']);
            unset($data['placement']);
        }
        if (\array_key_exists('numberOfWeeks', $data)) {
            $object->setNumberOfWeeks($data['numberOfWeeks']);
            unset($data['numberOfWeeks']);
        }
        if (\array_key_exists('startDate', $data)) {
            $object->setStartDate(\DateTime::createFromFormat('Y-m-d', $data['startDate'])->setTime(0, 0, 0));
            unset($data['startDate']);
        }
        if (\array_key_exists('endDate', $data)) {
            $object->setEndDate(\DateTime::createFromFormat('Y-m-d', $data['endDate'])->setTime(0, 0, 0));
            unset($data['endDate']);
        }
        if (\array_key_exists('temporary', $data)) {
            $object->setTemporary($data['temporary']);
            unset($data['temporary']);
        }
        if (\array_key_exists('state', $data)) {
            $values = [];
            foreach ($data['state'] as $value) {
                $values[] = $this->denormalizer->denormalize($value, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleState::class, 'json', $context);
            }
            $object->setState($values);
            unset($data['state']);
        }
        if (\array_key_exists('scheduleEntries', $data)) {
            $values_1 = [];
            foreach ($data['scheduleEntries'] as $value_1) {
                $values_1[] = $this->denormalizer->denormalize($value_1, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleEntry::class, 'json', $context);
            }
            $object->setScheduleEntries($values_1);
            unset($data['scheduleEntries']);
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
        $dataArray['placement'] = $data->getPlacement();
        $dataArray['numberOfWeeks'] = $data->getNumberOfWeeks();
        $dataArray['startDate'] = $data->getStartDate()?->format('Y-m-d');
        if ($data->isInitialized('endDate') && null !== $data->getEndDate()) {
            $dataArray['endDate'] = $data->getEndDate()?->format('Y-m-d');
        }
        if ($data->isInitialized('temporary') && null !== $data->getTemporary()) {
            $dataArray['temporary'] = $data->getTemporary();
        }
        $values = [];
        foreach ($data->getState() as $value) {
            $values[] = $this->normalizer->normalize($value, 'json', $context);
        }
        $dataArray['state'] = $values;
        if ($data->isInitialized('scheduleEntries') && null !== $data->getScheduleEntries()) {
            $values_1 = [];
            foreach ($data->getScheduleEntries() as $value_1) {
                $values_1[] = $this->normalizer->normalize($value_1, 'json', $context);
            }
            $dataArray['scheduleEntries'] = $values_1;
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule::class => false];
    }
}