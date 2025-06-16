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
class OrganisationNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation();
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
        if (\array_key_exists('organisationCode', $data)) {
            $object->setOrganisationCode($data['organisationCode']);
            unset($data['organisationCode']);
        }
        if (\array_key_exists('organisationType', $data)) {
            $object->setOrganisationType($data['organisationType']);
            unset($data['organisationType']);
        }
        if (\array_key_exists('organisationNumber', $data)) {
            $object->setOrganisationNumber($data['organisationNumber']);
            unset($data['organisationNumber']);
        }
        if (\array_key_exists('parentOrganisation', $data)) {
            $object->setParentOrganisation($data['parentOrganisation']);
            unset($data['parentOrganisation']);
        }
        if (\array_key_exists('schoolUnitCode', $data)) {
            $object->setSchoolUnitCode($data['schoolUnitCode']);
            unset($data['schoolUnitCode']);
        }
        if (\array_key_exists('schoolTypes', $data)) {
            $values = [];
            foreach ($data['schoolTypes'] as $value) {
                $values[] = $value;
            }
            $object->setSchoolTypes($values);
            unset($data['schoolTypes']);
        }
        if (\array_key_exists('address', $data)) {
            $object->setAddress($this->denormalizer->denormalize($data['address'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationAddress::class, 'json', $context));
            unset($data['address']);
        }
        if (\array_key_exists('municipalityCode', $data)) {
            $object->setMunicipalityCode($data['municipalityCode']);
            unset($data['municipalityCode']);
        }
        if (\array_key_exists('url', $data)) {
            $object->setUrl($data['url']);
            unset($data['url']);
        }
        if (\array_key_exists('email', $data)) {
            $object->setEmail($data['email']);
            unset($data['email']);
        }
        if (\array_key_exists('phoneNumber', $data)) {
            $object->setPhoneNumber($data['phoneNumber']);
            unset($data['phoneNumber']);
        }
        if (\array_key_exists('contactInfo', $data)) {
            $values_1 = [];
            foreach ($data['contactInfo'] as $value_1) {
                $values_1[] = $this->denormalizer->denormalize($value_1, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ContactInfo::class, 'json', $context);
            }
            $object->setContactInfo($values_1);
            unset($data['contactInfo']);
        }
        if (\array_key_exists('startDate', $data)) {
            $object->setStartDate(\DateTime::createFromFormat('Y-m-d', $data['startDate'])->setTime(0, 0, 0));
            unset($data['startDate']);
        }
        if (\array_key_exists('endDate', $data)) {
            $object->setEndDate(\DateTime::createFromFormat('Y-m-d', $data['endDate'])->setTime(0, 0, 0));
            unset($data['endDate']);
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
        $dataArray['id'] = $data->getId();
        $dataArray['displayName'] = $data->getDisplayName();
        if ($data->isInitialized('organisationCode') && null !== $data->getOrganisationCode()) {
            $dataArray['organisationCode'] = $data->getOrganisationCode();
        }
        $dataArray['organisationType'] = $data->getOrganisationType();
        if ($data->isInitialized('organisationNumber') && null !== $data->getOrganisationNumber()) {
            $dataArray['organisationNumber'] = $data->getOrganisationNumber();
        }
        if ($data->isInitialized('parentOrganisation') && null !== $data->getParentOrganisation()) {
            $dataArray['parentOrganisation'] = $data->getParentOrganisation();
        }
        if ($data->isInitialized('schoolUnitCode') && null !== $data->getSchoolUnitCode()) {
            $dataArray['schoolUnitCode'] = $data->getSchoolUnitCode();
        }
        if ($data->isInitialized('schoolTypes') && null !== $data->getSchoolTypes()) {
            $values = [];
            foreach ($data->getSchoolTypes() as $value) {
                $values[] = $value;
            }
            $dataArray['schoolTypes'] = $values;
        }
        if ($data->isInitialized('address') && null !== $data->getAddress()) {
            $dataArray['address'] = $this->normalizer->normalize($data->getAddress(), 'json', $context);
        }
        if ($data->isInitialized('municipalityCode') && null !== $data->getMunicipalityCode()) {
            $dataArray['municipalityCode'] = $data->getMunicipalityCode();
        }
        if ($data->isInitialized('url') && null !== $data->getUrl()) {
            $dataArray['url'] = $data->getUrl();
        }
        if ($data->isInitialized('email') && null !== $data->getEmail()) {
            $dataArray['email'] = $data->getEmail();
        }
        if ($data->isInitialized('phoneNumber') && null !== $data->getPhoneNumber()) {
            $dataArray['phoneNumber'] = $data->getPhoneNumber();
        }
        if ($data->isInitialized('contactInfo') && null !== $data->getContactInfo()) {
            $values_1 = [];
            foreach ($data->getContactInfo() as $value_1) {
                $values_1[] = $this->normalizer->normalize($value_1, 'json', $context);
            }
            $dataArray['contactInfo'] = $values_1;
        }
        if ($data->isInitialized('startDate') && null !== $data->getStartDate()) {
            $dataArray['startDate'] = $data->getStartDate()?->format('Y-m-d');
        }
        if ($data->isInitialized('endDate') && null !== $data->getEndDate()) {
            $dataArray['endDate'] = $data->getEndDate()?->format('Y-m-d');
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation::class => false];
    }
}