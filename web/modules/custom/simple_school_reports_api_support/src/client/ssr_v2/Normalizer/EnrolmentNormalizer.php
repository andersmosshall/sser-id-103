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
class EnrolmentNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Enrolment::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Enrolment::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Enrolment();
        if (\array_key_exists('cancelled', $data) && \is_int($data['cancelled'])) {
            $data['cancelled'] = (bool) $data['cancelled'];
        }
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('enroledAt', $data)) {
            $object->setEnroledAt($this->denormalizer->denormalize($data['enroledAt'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitReference::class, 'json', $context));
            unset($data['enroledAt']);
        }
        if (\array_key_exists('schoolYear', $data)) {
            $object->setSchoolYear($data['schoolYear']);
            unset($data['schoolYear']);
        }
        if (\array_key_exists('schoolType', $data)) {
            $object->setSchoolType($data['schoolType']);
            unset($data['schoolType']);
        }
        if (\array_key_exists('startDate', $data)) {
            $object->setStartDate(\DateTime::createFromFormat('Y-m-d', $data['startDate'])->setTime(0, 0, 0));
            unset($data['startDate']);
        }
        if (\array_key_exists('endDate', $data)) {
            $object->setEndDate(\DateTime::createFromFormat('Y-m-d', $data['endDate'])->setTime(0, 0, 0));
            unset($data['endDate']);
        }
        if (\array_key_exists('cancelled', $data)) {
            $object->setCancelled($data['cancelled']);
            unset($data['cancelled']);
        }
        if (\array_key_exists('educationCode', $data)) {
            $object->setEducationCode($data['educationCode']);
            unset($data['educationCode']);
        }
        if (\array_key_exists('programme', $data)) {
            $object->setProgramme($data['programme']);
            unset($data['programme']);
        }
        if (\array_key_exists('specification', $data)) {
            $object->setSpecification($data['specification']);
            unset($data['specification']);
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
        $dataArray['enroledAt'] = $this->normalizer->normalize($data->getEnroledAt(), 'json', $context);
        if ($data->isInitialized('schoolYear') && null !== $data->getSchoolYear()) {
            $dataArray['schoolYear'] = $data->getSchoolYear();
        }
        $dataArray['schoolType'] = $data->getSchoolType();
        $dataArray['startDate'] = $data->getStartDate()?->format('Y-m-d');
        if ($data->isInitialized('endDate') && null !== $data->getEndDate()) {
            $dataArray['endDate'] = $data->getEndDate()?->format('Y-m-d');
        }
        if ($data->isInitialized('cancelled') && null !== $data->getCancelled()) {
            $dataArray['cancelled'] = $data->getCancelled();
        }
        if ($data->isInitialized('educationCode') && null !== $data->getEducationCode()) {
            $dataArray['educationCode'] = $data->getEducationCode();
        }
        if ($data->isInitialized('programme') && null !== $data->getProgramme()) {
            $dataArray['programme'] = $data->getProgramme();
        }
        if ($data->isInitialized('specification') && null !== $data->getSpecification()) {
            $dataArray['specification'] = $data->getSpecification();
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Enrolment::class => false];
    }
}