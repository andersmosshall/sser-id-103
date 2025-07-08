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
class DutyNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duty::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duty::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duty();
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
        if (\array_key_exists('person', $data)) {
            $object->setPerson($data['person']);
            unset($data['person']);
        }
        if (\array_key_exists('assignmentRole', $data)) {
            $values = [];
            foreach ($data['assignmentRole'] as $value) {
                $values[] = $this->denormalizer->denormalize($value, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyAssignmentRoleInner::class, 'json', $context);
            }
            $object->setAssignmentRole($values);
            unset($data['assignmentRole']);
        }
        if (\array_key_exists('dutyAt', $data)) {
            $object->setDutyAt($this->denormalizer->denormalize($data['dutyAt'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationReference::class, 'json', $context));
            unset($data['dutyAt']);
        }
        if (\array_key_exists('dutyRole', $data)) {
            $object->setDutyRole($data['dutyRole']);
            unset($data['dutyRole']);
        }
        if (\array_key_exists('description', $data)) {
            $object->setDescription($data['description']);
            unset($data['description']);
        }
        if (\array_key_exists('signature', $data)) {
            $object->setSignature($data['signature']);
            unset($data['signature']);
        }
        if (\array_key_exists('dutyPercent', $data)) {
            $object->setDutyPercent($data['dutyPercent']);
            unset($data['dutyPercent']);
        }
        if (\array_key_exists('hoursPerYear', $data)) {
            $object->setHoursPerYear($data['hoursPerYear']);
            unset($data['hoursPerYear']);
        }
        if (\array_key_exists('startDate', $data)) {
            $object->setStartDate(\DateTime::createFromFormat('Y-m-d', $data['startDate'])->setTime(0, 0, 0));
            unset($data['startDate']);
        }
        if (\array_key_exists('endDate', $data)) {
            $object->setEndDate(\DateTime::createFromFormat('Y-m-d', $data['endDate'])->setTime(0, 0, 0));
            unset($data['endDate']);
        }
        foreach ($data as $key => $value_1) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value_1;
            }
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['id'] = $data->getId();
        if ($data->isInitialized('person') && null !== $data->getPerson()) {
            $dataArray['person'] = $data->getPerson();
        }
        if ($data->isInitialized('assignmentRole') && null !== $data->getAssignmentRole()) {
            $values = [];
            foreach ($data->getAssignmentRole() as $value) {
                $values[] = $this->normalizer->normalize($value, 'json', $context);
            }
            $dataArray['assignmentRole'] = $values;
        }
        $dataArray['dutyAt'] = $this->normalizer->normalize($data->getDutyAt(), 'json', $context);
        $dataArray['dutyRole'] = $data->getDutyRole();
        if ($data->isInitialized('description') && null !== $data->getDescription()) {
            $dataArray['description'] = $data->getDescription();
        }
        if ($data->isInitialized('signature') && null !== $data->getSignature()) {
            $dataArray['signature'] = $data->getSignature();
        }
        if ($data->isInitialized('dutyPercent') && null !== $data->getDutyPercent()) {
            $dataArray['dutyPercent'] = $data->getDutyPercent();
        }
        if ($data->isInitialized('hoursPerYear') && null !== $data->getHoursPerYear()) {
            $dataArray['hoursPerYear'] = $data->getHoursPerYear();
        }
        $dataArray['startDate'] = $data->getStartDate()?->format('Y-m-d');
        if ($data->isInitialized('endDate') && null !== $data->getEndDate()) {
            $dataArray['endDate'] = $data->getEndDate()?->format('Y-m-d');
        }
        foreach ($data as $key => $value_1) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value_1;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duty::class => false];
    }
}