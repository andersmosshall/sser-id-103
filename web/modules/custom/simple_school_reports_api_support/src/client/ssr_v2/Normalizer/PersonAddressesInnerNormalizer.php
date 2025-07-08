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
class PersonAddressesInnerNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonAddressesInner::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonAddressesInner::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonAddressesInner();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('type', $data)) {
            $object->setType($data['type']);
            unset($data['type']);
        }
        if (\array_key_exists('streetAddress', $data)) {
            $object->setStreetAddress($data['streetAddress']);
            unset($data['streetAddress']);
        }
        if (\array_key_exists('locality', $data)) {
            $object->setLocality($data['locality']);
            unset($data['locality']);
        }
        if (\array_key_exists('postalCode', $data)) {
            $object->setPostalCode($data['postalCode']);
            unset($data['postalCode']);
        }
        if (\array_key_exists('countyCode', $data)) {
            $object->setCountyCode($data['countyCode']);
            unset($data['countyCode']);
        }
        if (\array_key_exists('municipalityCode', $data)) {
            $object->setMunicipalityCode($data['municipalityCode']);
            unset($data['municipalityCode']);
        }
        if (\array_key_exists('realEstateDesignation', $data)) {
            $object->setRealEstateDesignation($data['realEstateDesignation']);
            unset($data['realEstateDesignation']);
        }
        if (\array_key_exists('country', $data)) {
            $object->setCountry($data['country']);
            unset($data['country']);
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
        if ($data->isInitialized('type') && null !== $data->getType()) {
            $dataArray['type'] = $data->getType();
        }
        $dataArray['streetAddress'] = $data->getStreetAddress();
        $dataArray['locality'] = $data->getLocality();
        $dataArray['postalCode'] = $data->getPostalCode();
        if ($data->isInitialized('countyCode') && null !== $data->getCountyCode()) {
            $dataArray['countyCode'] = $data->getCountyCode();
        }
        if ($data->isInitialized('municipalityCode') && null !== $data->getMunicipalityCode()) {
            $dataArray['municipalityCode'] = $data->getMunicipalityCode();
        }
        if ($data->isInitialized('realEstateDesignation') && null !== $data->getRealEstateDesignation()) {
            $dataArray['realEstateDesignation'] = $data->getRealEstateDesignation();
        }
        $dataArray['country'] = $data->getCountry();
        foreach ($data as $key => $value) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonAddressesInner::class => false];
    }
}