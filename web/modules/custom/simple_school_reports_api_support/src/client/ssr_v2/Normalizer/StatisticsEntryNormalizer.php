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
class StatisticsEntryNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StatisticsEntry::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StatisticsEntry::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StatisticsEntry();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('resourceType', $data)) {
            $object->setResourceType($data['resourceType']);
            unset($data['resourceType']);
        }
        if (\array_key_exists('newCount', $data)) {
            $object->setNewCount($data['newCount']);
            unset($data['newCount']);
        }
        if (\array_key_exists('updatedCount', $data)) {
            $object->setUpdatedCount($data['updatedCount']);
            unset($data['updatedCount']);
        }
        if (\array_key_exists('deletedCount', $data)) {
            $object->setDeletedCount($data['deletedCount']);
            unset($data['deletedCount']);
        }
        if (\array_key_exists('resourceUrl', $data)) {
            $object->setResourceUrl($data['resourceUrl']);
            unset($data['resourceUrl']);
        }
        if (\array_key_exists('timeOfOccurance', $data)) {
            $object->setTimeOfOccurance(\DateTime::createFromFormat('Y-m-d\TH:i:sP', $data['timeOfOccurance']));
            unset($data['timeOfOccurance']);
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
        $dataArray['resourceType'] = $data->getResourceType();
        $dataArray['newCount'] = $data->getNewCount();
        $dataArray['updatedCount'] = $data->getUpdatedCount();
        $dataArray['deletedCount'] = $data->getDeletedCount();
        if ($data->isInitialized('resourceUrl') && null !== $data->getResourceUrl()) {
            $dataArray['resourceUrl'] = $data->getResourceUrl();
        }
        if ($data->isInitialized('timeOfOccurance') && null !== $data->getTimeOfOccurance()) {
            $dataArray['timeOfOccurance'] = $data->getTimeOfOccurance()?->format('Y-m-d\TH:i:sP');
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StatisticsEntry::class => false];
    }
}