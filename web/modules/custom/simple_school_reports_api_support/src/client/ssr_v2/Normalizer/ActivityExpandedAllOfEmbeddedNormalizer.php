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
class ActivityExpandedAllOfEmbeddedNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpandedAllOfEmbedded::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpandedAllOfEmbedded::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpandedAllOfEmbedded();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('groups', $data)) {
            $values = [];
            foreach ($data['groups'] as $value) {
                $values[] = $this->denormalizer->denormalize($value, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Group::class, 'json', $context);
            }
            $object->setGroups($values);
            unset($data['groups']);
        }
        if (\array_key_exists('syllabus', $data)) {
            $object->setSyllabus($this->denormalizer->denormalize($data['syllabus'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus::class, 'json', $context));
            unset($data['syllabus']);
        }
        if (\array_key_exists('teachers', $data)) {
            $values_1 = [];
            foreach ($data['teachers'] as $value_1) {
                $values_1[] = $this->denormalizer->denormalize($value_1, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duty::class, 'json', $context);
            }
            $object->setTeachers($values_1);
            unset($data['teachers']);
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
        if ($data->isInitialized('groups') && null !== $data->getGroups()) {
            $values = [];
            foreach ($data->getGroups() as $value) {
                $values[] = $this->normalizer->normalize($value, 'json', $context);
            }
            $dataArray['groups'] = $values;
        }
        if ($data->isInitialized('syllabus') && null !== $data->getSyllabus()) {
            $dataArray['syllabus'] = $this->normalizer->normalize($data->getSyllabus(), 'json', $context);
        }
        if ($data->isInitialized('teachers') && null !== $data->getTeachers()) {
            $values_1 = [];
            foreach ($data->getTeachers() as $value_1) {
                $values_1[] = $this->normalizer->normalize($value_1, 'json', $context);
            }
            $dataArray['teachers'] = $values_1;
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpandedAllOfEmbedded::class => false];
    }
}