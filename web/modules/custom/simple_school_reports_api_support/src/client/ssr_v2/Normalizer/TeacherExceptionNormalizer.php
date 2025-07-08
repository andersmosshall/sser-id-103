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
class TeacherExceptionNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\TeacherException::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\TeacherException::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\TeacherException();
        if (\array_key_exists('participates', $data) && \is_int($data['participates'])) {
            $data['participates'] = (bool) $data['participates'];
        }
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('duty', $data)) {
            $object->setDuty($this->denormalizer->denormalize($data['duty'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyReference::class, 'json', $context));
            unset($data['duty']);
        }
        if (\array_key_exists('participates', $data)) {
            $object->setParticipates($data['participates']);
            unset($data['participates']);
        }
        if (\array_key_exists('startTime', $data)) {
            $object->setStartTime(\DateTime::createFromFormat('Y-m-d\TH:i:sP', $data['startTime']));
            unset($data['startTime']);
        }
        if (\array_key_exists('endTime', $data)) {
            $object->setEndTime(\DateTime::createFromFormat('Y-m-d\TH:i:sP', $data['endTime']));
            unset($data['endTime']);
        }
        if (\array_key_exists('teachingLength', $data)) {
            $object->setTeachingLength($data['teachingLength']);
            unset($data['teachingLength']);
        }
        foreach ($data as $key => $value) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value;
            }
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['duty'] = $this->normalizer->normalize($data->getDuty(), 'json', $context);
        $dataArray['participates'] = $data->getParticipates();
        if ($data->isInitialized('startTime') && null !== $data->getStartTime()) {
            $dataArray['startTime'] = $data->getStartTime()?->format('Y-m-d\TH:i:sP');
        }
        if ($data->isInitialized('endTime') && null !== $data->getEndTime()) {
            $dataArray['endTime'] = $data->getEndTime()?->format('Y-m-d\TH:i:sP');
        }
        if ($data->isInitialized('teachingLength') && null !== $data->getTeachingLength()) {
            $dataArray['teachingLength'] = $data->getTeachingLength();
        }
        foreach ($data as $key => $value) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\TeacherException::class => false];
    }
}