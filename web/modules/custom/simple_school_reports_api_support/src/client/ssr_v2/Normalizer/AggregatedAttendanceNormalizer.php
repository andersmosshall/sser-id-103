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
class AggregatedAttendanceNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendance::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendance::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendance();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('activity', $data)) {
            $object->setActivity($this->denormalizer->denormalize($data['activity'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityReference::class, 'json', $context));
            unset($data['activity']);
        }
        if (\array_key_exists('student', $data)) {
            $object->setStudent($data['student']);
            unset($data['student']);
        }
        if (\array_key_exists('startDate', $data)) {
            $object->setStartDate(\DateTime::createFromFormat('Y-m-d', $data['startDate'])->setTime(0, 0, 0));
            unset($data['startDate']);
        }
        if (\array_key_exists('endDate', $data)) {
            $object->setEndDate(\DateTime::createFromFormat('Y-m-d', $data['endDate'])->setTime(0, 0, 0));
            unset($data['endDate']);
        }
        if (\array_key_exists('attendanceSum', $data)) {
            $object->setAttendanceSum($data['attendanceSum']);
            unset($data['attendanceSum']);
        }
        if (\array_key_exists('validAbsenceSum', $data)) {
            $object->setValidAbsenceSum($data['validAbsenceSum']);
            unset($data['validAbsenceSum']);
        }
        if (\array_key_exists('invalidAbsenceSum', $data)) {
            $object->setInvalidAbsenceSum($data['invalidAbsenceSum']);
            unset($data['invalidAbsenceSum']);
        }
        if (\array_key_exists('otherAttendanceSum', $data)) {
            $object->setOtherAttendanceSum($data['otherAttendanceSum']);
            unset($data['otherAttendanceSum']);
        }
        if (\array_key_exists('reportedSum', $data)) {
            $object->setReportedSum($data['reportedSum']);
            unset($data['reportedSum']);
        }
        if (\array_key_exists('offeredSum', $data)) {
            $object->setOfferedSum($data['offeredSum']);
            unset($data['offeredSum']);
        }
        if (\array_key_exists('_embedded', $data)) {
            $object->setEmbedded($this->denormalizer->denormalize($data['_embedded'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendanceEmbedded::class, 'json', $context));
            unset($data['_embedded']);
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
        $dataArray['activity'] = $this->normalizer->normalize($data->getActivity(), 'json', $context);
        $dataArray['student'] = $data->getStudent();
        $dataArray['startDate'] = $data->getStartDate()?->format('Y-m-d');
        $dataArray['endDate'] = $data->getEndDate()?->format('Y-m-d');
        $dataArray['attendanceSum'] = $data->getAttendanceSum();
        if ($data->isInitialized('validAbsenceSum') && null !== $data->getValidAbsenceSum()) {
            $dataArray['validAbsenceSum'] = $data->getValidAbsenceSum();
        }
        if ($data->isInitialized('invalidAbsenceSum') && null !== $data->getInvalidAbsenceSum()) {
            $dataArray['invalidAbsenceSum'] = $data->getInvalidAbsenceSum();
        }
        if ($data->isInitialized('otherAttendanceSum') && null !== $data->getOtherAttendanceSum()) {
            $dataArray['otherAttendanceSum'] = $data->getOtherAttendanceSum();
        }
        if ($data->isInitialized('reportedSum') && null !== $data->getReportedSum()) {
            $dataArray['reportedSum'] = $data->getReportedSum();
        }
        if ($data->isInitialized('offeredSum') && null !== $data->getOfferedSum()) {
            $dataArray['offeredSum'] = $data->getOfferedSum();
        }
        if ($data->isInitialized('embedded') && null !== $data->getEmbedded()) {
            $dataArray['_embedded'] = $this->normalizer->normalize($data->getEmbedded(), 'json', $context);
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendance::class => false];
    }
}