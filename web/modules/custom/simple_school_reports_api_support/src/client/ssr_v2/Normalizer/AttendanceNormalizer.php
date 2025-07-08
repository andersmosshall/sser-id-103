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
class AttendanceNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance();
        if (\array_key_exists('isReported', $data) && \is_int($data['isReported'])) {
            $data['isReported'] = (bool) $data['isReported'];
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
        if (\array_key_exists('calendarEvent', $data)) {
            $object->setCalendarEvent($this->denormalizer->denormalize($data['calendarEvent'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventReference::class, 'json', $context));
            unset($data['calendarEvent']);
        }
        if (\array_key_exists('student', $data)) {
            $object->setStudent($data['student']);
            unset($data['student']);
        }
        if (\array_key_exists('reporter', $data)) {
            $object->setReporter($data['reporter']);
            unset($data['reporter']);
        }
        if (\array_key_exists('isReported', $data)) {
            $object->setIsReported($data['isReported']);
            unset($data['isReported']);
        }
        if (\array_key_exists('attendanceMinutes', $data)) {
            $object->setAttendanceMinutes($data['attendanceMinutes']);
            unset($data['attendanceMinutes']);
        }
        if (\array_key_exists('validAbsenceMinutes', $data)) {
            $object->setValidAbsenceMinutes($data['validAbsenceMinutes']);
            unset($data['validAbsenceMinutes']);
        }
        if (\array_key_exists('invalidAbsenceMinutes', $data)) {
            $object->setInvalidAbsenceMinutes($data['invalidAbsenceMinutes']);
            unset($data['invalidAbsenceMinutes']);
        }
        if (\array_key_exists('otherAttendanceMinutes', $data)) {
            $object->setOtherAttendanceMinutes($data['otherAttendanceMinutes']);
            unset($data['otherAttendanceMinutes']);
        }
        if (\array_key_exists('absenceReason', $data)) {
            $object->setAbsenceReason($data['absenceReason']);
            unset($data['absenceReason']);
        }
        if (\array_key_exists('reportedTimestamp', $data)) {
            $object->setReportedTimestamp(\DateTime::createFromFormat('Y-m-d\TH:i:sP', $data['reportedTimestamp']));
            unset($data['reportedTimestamp']);
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
        $dataArray['calendarEvent'] = $this->normalizer->normalize($data->getCalendarEvent(), 'json', $context);
        $dataArray['student'] = $data->getStudent();
        if ($data->isInitialized('reporter') && null !== $data->getReporter()) {
            $dataArray['reporter'] = $data->getReporter();
        }
        $dataArray['isReported'] = $data->getIsReported();
        if ($data->isInitialized('attendanceMinutes') && null !== $data->getAttendanceMinutes()) {
            $dataArray['attendanceMinutes'] = $data->getAttendanceMinutes();
        }
        if ($data->isInitialized('validAbsenceMinutes') && null !== $data->getValidAbsenceMinutes()) {
            $dataArray['validAbsenceMinutes'] = $data->getValidAbsenceMinutes();
        }
        if ($data->isInitialized('invalidAbsenceMinutes') && null !== $data->getInvalidAbsenceMinutes()) {
            $dataArray['invalidAbsenceMinutes'] = $data->getInvalidAbsenceMinutes();
        }
        if ($data->isInitialized('otherAttendanceMinutes') && null !== $data->getOtherAttendanceMinutes()) {
            $dataArray['otherAttendanceMinutes'] = $data->getOtherAttendanceMinutes();
        }
        if ($data->isInitialized('absenceReason') && null !== $data->getAbsenceReason()) {
            $dataArray['absenceReason'] = $data->getAbsenceReason();
        }
        if ($data->isInitialized('reportedTimestamp') && null !== $data->getReportedTimestamp()) {
            $dataArray['reportedTimestamp'] = $data->getReportedTimestamp()?->format('Y-m-d\TH:i:sP');
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance::class => false];
    }
}