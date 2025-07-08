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
class GroupExpandedNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpanded::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpanded::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpanded();
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
        if (\array_key_exists('startDate', $data)) {
            $object->setStartDate(\DateTime::createFromFormat('Y-m-d', $data['startDate'])->setTime(0, 0, 0));
            unset($data['startDate']);
        }
        if (\array_key_exists('endDate', $data)) {
            $object->setEndDate(\DateTime::createFromFormat('Y-m-d', $data['endDate'])->setTime(0, 0, 0));
            unset($data['endDate']);
        }
        if (\array_key_exists('groupType', $data)) {
            $object->setGroupType($data['groupType']);
            unset($data['groupType']);
        }
        if (\array_key_exists('schoolType', $data)) {
            $object->setSchoolType($data['schoolType']);
            unset($data['schoolType']);
        }
        if (\array_key_exists('organisation', $data)) {
            $object->setOrganisation($this->denormalizer->denormalize($data['organisation'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationReference::class, 'json', $context));
            unset($data['organisation']);
        }
        if (\array_key_exists('groupMemberships', $data)) {
            $values = [];
            foreach ($data['groupMemberships'] as $value) {
                $values[] = $this->denormalizer->denormalize($value, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupMembership::class, 'json', $context);
            }
            $object->setGroupMemberships($values);
            unset($data['groupMemberships']);
        }
        if (\array_key_exists('_embedded', $data)) {
            $object->setEmbedded($this->denormalizer->denormalize($data['_embedded'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpandedAllOfEmbedded::class, 'json', $context));
            unset($data['_embedded']);
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
        $dataArray['displayName'] = $data->getDisplayName();
        $dataArray['startDate'] = $data->getStartDate()?->format('Y-m-d');
        if ($data->isInitialized('endDate') && null !== $data->getEndDate()) {
            $dataArray['endDate'] = $data->getEndDate()?->format('Y-m-d');
        }
        $dataArray['groupType'] = $data->getGroupType();
        if ($data->isInitialized('schoolType') && null !== $data->getSchoolType()) {
            $dataArray['schoolType'] = $data->getSchoolType();
        }
        $dataArray['organisation'] = $this->normalizer->normalize($data->getOrganisation(), 'json', $context);
        if ($data->isInitialized('groupMemberships') && null !== $data->getGroupMemberships()) {
            $values = [];
            foreach ($data->getGroupMemberships() as $value) {
                $values[] = $this->normalizer->normalize($value, 'json', $context);
            }
            $dataArray['groupMemberships'] = $values;
        }
        if ($data->isInitialized('embedded') && null !== $data->getEmbedded()) {
            $dataArray['_embedded'] = $this->normalizer->normalize($data->getEmbedded(), 'json', $context);
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpanded::class => false];
    }
}