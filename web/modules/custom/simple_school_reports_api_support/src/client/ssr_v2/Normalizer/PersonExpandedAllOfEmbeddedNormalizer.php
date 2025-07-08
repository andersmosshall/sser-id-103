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
class PersonExpandedAllOfEmbeddedNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbedded::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbedded::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbedded();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('responsibleFor', $data)) {
            $values = [];
            foreach ($data['responsibleFor'] as $value) {
                $values[] = $this->denormalizer->denormalize($value, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonResponsiblesInner::class, 'json', $context);
            }
            $object->setResponsibleFor($values);
            unset($data['responsibleFor']);
        }
        if (\array_key_exists('placements', $data)) {
            $values_1 = [];
            foreach ($data['placements'] as $value_1) {
                $values_1[] = $this->denormalizer->denormalize($value_1, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Placement::class, 'json', $context);
            }
            $object->setPlacements($values_1);
            unset($data['placements']);
        }
        if (\array_key_exists('ownedPlacements', $data)) {
            $values_2 = [];
            foreach ($data['ownedPlacements'] as $value_2) {
                $values_2[] = $this->denormalizer->denormalize($value_2, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Placement::class, 'json', $context);
            }
            $object->setOwnedPlacements($values_2);
            unset($data['ownedPlacements']);
        }
        if (\array_key_exists('duties', $data)) {
            $values_3 = [];
            foreach ($data['duties'] as $value_3) {
                $values_3[] = $this->denormalizer->denormalize($value_3, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duty::class, 'json', $context);
            }
            $object->setDuties($values_3);
            unset($data['duties']);
        }
        if (\array_key_exists('groupMemberships', $data)) {
            $values_4 = [];
            foreach ($data['groupMemberships'] as $value_4) {
                $values_4[] = $this->denormalizer->denormalize($value_4, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbeddedGroupMemberships::class, 'json', $context);
            }
            $object->setGroupMemberships($values_4);
            unset($data['groupMemberships']);
        }
        foreach ($data as $key => $value_5) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value_5;
            }
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        if ($data->isInitialized('responsibleFor') && null !== $data->getResponsibleFor()) {
            $values = [];
            foreach ($data->getResponsibleFor() as $value) {
                $values[] = $this->normalizer->normalize($value, 'json', $context);
            }
            $dataArray['responsibleFor'] = $values;
        }
        if ($data->isInitialized('placements') && null !== $data->getPlacements()) {
            $values_1 = [];
            foreach ($data->getPlacements() as $value_1) {
                $values_1[] = $this->normalizer->normalize($value_1, 'json', $context);
            }
            $dataArray['placements'] = $values_1;
        }
        if ($data->isInitialized('ownedPlacements') && null !== $data->getOwnedPlacements()) {
            $values_2 = [];
            foreach ($data->getOwnedPlacements() as $value_2) {
                $values_2[] = $this->normalizer->normalize($value_2, 'json', $context);
            }
            $dataArray['ownedPlacements'] = $values_2;
        }
        if ($data->isInitialized('duties') && null !== $data->getDuties()) {
            $values_3 = [];
            foreach ($data->getDuties() as $value_3) {
                $values_3[] = $this->normalizer->normalize($value_3, 'json', $context);
            }
            $dataArray['duties'] = $values_3;
        }
        if ($data->isInitialized('groupMemberships') && null !== $data->getGroupMemberships()) {
            $values_4 = [];
            foreach ($data->getGroupMemberships() as $value_4) {
                $values_4[] = $this->normalizer->normalize($value_4, 'json', $context);
            }
            $dataArray['groupMemberships'] = $values_4;
        }
        foreach ($data as $key => $value_5) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value_5;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbedded::class => false];
    }
}