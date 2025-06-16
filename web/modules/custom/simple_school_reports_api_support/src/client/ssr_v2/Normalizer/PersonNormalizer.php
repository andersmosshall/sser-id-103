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
class PersonNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Person::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Person::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Person();
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
        if (\array_key_exists('givenName', $data)) {
            $object->setGivenName($data['givenName']);
            unset($data['givenName']);
        }
        if (\array_key_exists('middleName', $data)) {
            $object->setMiddleName($data['middleName']);
            unset($data['middleName']);
        }
        if (\array_key_exists('familyName', $data)) {
            $object->setFamilyName($data['familyName']);
            unset($data['familyName']);
        }
        if (\array_key_exists('eduPersonPrincipalNames', $data)) {
            $values = [];
            foreach ($data['eduPersonPrincipalNames'] as $value) {
                $values[] = $value;
            }
            $object->setEduPersonPrincipalNames($values);
            unset($data['eduPersonPrincipalNames']);
        }
        if (\array_key_exists('externalIdentifiers', $data)) {
            $values_1 = [];
            foreach ($data['externalIdentifiers'] as $value_1) {
                $values_1[] = $this->denormalizer->denormalize($value_1, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ExternalIdentifier::class, 'json', $context);
            }
            $object->setExternalIdentifiers($values_1);
            unset($data['externalIdentifiers']);
        }
        if (\array_key_exists('civicNo', $data)) {
            $object->setCivicNo($this->denormalizer->denormalize($data['civicNo'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonCivicNo::class, 'json', $context));
            unset($data['civicNo']);
        }
        if (\array_key_exists('birthDate', $data)) {
            $object->setBirthDate(\DateTime::createFromFormat('Y-m-d', $data['birthDate'])->setTime(0, 0, 0));
            unset($data['birthDate']);
        }
        if (\array_key_exists('sex', $data)) {
            $object->setSex($data['sex']);
            unset($data['sex']);
        }
        if (\array_key_exists('securityMarking', $data)) {
            $object->setSecurityMarking($data['securityMarking']);
            unset($data['securityMarking']);
        }
        if (\array_key_exists('personStatus', $data)) {
            $object->setPersonStatus($data['personStatus']);
            unset($data['personStatus']);
        }
        if (\array_key_exists('emails', $data)) {
            $values_2 = [];
            foreach ($data['emails'] as $value_2) {
                $values_2[] = $this->denormalizer->denormalize($value_2, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Email::class, 'json', $context);
            }
            $object->setEmails($values_2);
            unset($data['emails']);
        }
        if (\array_key_exists('phoneNumbers', $data)) {
            $values_3 = [];
            foreach ($data['phoneNumbers'] as $value_3) {
                $values_3[] = $this->denormalizer->denormalize($value_3, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Phonenumber::class, 'json', $context);
            }
            $object->setPhoneNumbers($values_3);
            unset($data['phoneNumbers']);
        }
        if (\array_key_exists('addresses', $data)) {
            $values_4 = [];
            foreach ($data['addresses'] as $value_4) {
                $values_4[] = $this->denormalizer->denormalize($value_4, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonAddressesInner::class, 'json', $context);
            }
            $object->setAddresses($values_4);
            unset($data['addresses']);
        }
        if (\array_key_exists('photo', $data)) {
            $object->setPhoto($data['photo']);
            unset($data['photo']);
        }
        if (\array_key_exists('enrolments', $data)) {
            $values_5 = [];
            foreach ($data['enrolments'] as $value_5) {
                $values_5[] = $this->denormalizer->denormalize($value_5, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Enrolment::class, 'json', $context);
            }
            $object->setEnrolments($values_5);
            unset($data['enrolments']);
        }
        if (\array_key_exists('responsibles', $data)) {
            $values_6 = [];
            foreach ($data['responsibles'] as $value_6) {
                $values_6[] = $this->denormalizer->denormalize($value_6, \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonResponsiblesInner::class, 'json', $context);
            }
            $object->setResponsibles($values_6);
            unset($data['responsibles']);
        }
        foreach ($data as $key => $value_7) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value_7;
            }
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['id'] = $data->getId();
        $dataArray['givenName'] = $data->getGivenName();
        if ($data->isInitialized('middleName') && null !== $data->getMiddleName()) {
            $dataArray['middleName'] = $data->getMiddleName();
        }
        $dataArray['familyName'] = $data->getFamilyName();
        if ($data->isInitialized('eduPersonPrincipalNames') && null !== $data->getEduPersonPrincipalNames()) {
            $values = [];
            foreach ($data->getEduPersonPrincipalNames() as $value) {
                $values[] = $value;
            }
            $dataArray['eduPersonPrincipalNames'] = $values;
        }
        if ($data->isInitialized('externalIdentifiers') && null !== $data->getExternalIdentifiers()) {
            $values_1 = [];
            foreach ($data->getExternalIdentifiers() as $value_1) {
                $values_1[] = $this->normalizer->normalize($value_1, 'json', $context);
            }
            $dataArray['externalIdentifiers'] = $values_1;
        }
        if ($data->isInitialized('civicNo') && null !== $data->getCivicNo()) {
            $dataArray['civicNo'] = $this->normalizer->normalize($data->getCivicNo(), 'json', $context);
        }
        if ($data->isInitialized('birthDate') && null !== $data->getBirthDate()) {
            $dataArray['birthDate'] = $data->getBirthDate()?->format('Y-m-d');
        }
        if ($data->isInitialized('sex') && null !== $data->getSex()) {
            $dataArray['sex'] = $data->getSex();
        }
        if ($data->isInitialized('securityMarking') && null !== $data->getSecurityMarking()) {
            $dataArray['securityMarking'] = $data->getSecurityMarking();
        }
        if ($data->isInitialized('personStatus') && null !== $data->getPersonStatus()) {
            $dataArray['personStatus'] = $data->getPersonStatus();
        }
        if ($data->isInitialized('emails') && null !== $data->getEmails()) {
            $values_2 = [];
            foreach ($data->getEmails() as $value_2) {
                $values_2[] = $this->normalizer->normalize($value_2, 'json', $context);
            }
            $dataArray['emails'] = $values_2;
        }
        if ($data->isInitialized('phoneNumbers') && null !== $data->getPhoneNumbers()) {
            $values_3 = [];
            foreach ($data->getPhoneNumbers() as $value_3) {
                $values_3[] = $this->normalizer->normalize($value_3, 'json', $context);
            }
            $dataArray['phoneNumbers'] = $values_3;
        }
        if ($data->isInitialized('addresses') && null !== $data->getAddresses()) {
            $values_4 = [];
            foreach ($data->getAddresses() as $value_4) {
                $values_4[] = $this->normalizer->normalize($value_4, 'json', $context);
            }
            $dataArray['addresses'] = $values_4;
        }
        if ($data->isInitialized('photo') && null !== $data->getPhoto()) {
            $dataArray['photo'] = $data->getPhoto();
        }
        if ($data->isInitialized('enrolments') && null !== $data->getEnrolments()) {
            $values_5 = [];
            foreach ($data->getEnrolments() as $value_5) {
                $values_5[] = $this->normalizer->normalize($value_5, 'json', $context);
            }
            $dataArray['enrolments'] = $values_5;
        }
        if ($data->isInitialized('responsibles') && null !== $data->getResponsibles()) {
            $values_6 = [];
            foreach ($data->getResponsibles() as $value_6) {
                $values_6[] = $this->normalizer->normalize($value_6, 'json', $context);
            }
            $dataArray['responsibles'] = $values_6;
        }
        foreach ($data as $key => $value_7) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value_7;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Person::class => false];
    }
}