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
class SyllabusNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus();
        if (\array_key_exists('official', $data) && \is_int($data['official'])) {
            $data['official'] = (bool) $data['official'];
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
        if (\array_key_exists('schoolType', $data)) {
            $object->setSchoolType($data['schoolType']);
            unset($data['schoolType']);
        }
        if (\array_key_exists('subjectCode', $data)) {
            $object->setSubjectCode($data['subjectCode']);
            unset($data['subjectCode']);
        }
        if (\array_key_exists('subjectName', $data)) {
            $object->setSubjectName($data['subjectName']);
            unset($data['subjectName']);
        }
        if (\array_key_exists('subjectDesignation', $data)) {
            $object->setSubjectDesignation($data['subjectDesignation']);
            unset($data['subjectDesignation']);
        }
        if (\array_key_exists('courseCode', $data)) {
            $object->setCourseCode($data['courseCode']);
            unset($data['courseCode']);
        }
        if (\array_key_exists('courseName', $data)) {
            $object->setCourseName($data['courseName']);
            unset($data['courseName']);
        }
        if (\array_key_exists('startSchoolYear', $data)) {
            $object->setStartSchoolYear($data['startSchoolYear']);
            unset($data['startSchoolYear']);
        }
        if (\array_key_exists('endSchoolYear', $data)) {
            $object->setEndSchoolYear($data['endSchoolYear']);
            unset($data['endSchoolYear']);
        }
        if (\array_key_exists('points', $data)) {
            $object->setPoints($data['points']);
            unset($data['points']);
        }
        if (\array_key_exists('curriculum', $data)) {
            $object->setCurriculum($data['curriculum']);
            unset($data['curriculum']);
        }
        if (\array_key_exists('languageCode', $data)) {
            $object->setLanguageCode($data['languageCode']);
            unset($data['languageCode']);
        }
        if (\array_key_exists('specialisationCourseContent', $data)) {
            $object->setSpecialisationCourseContent($this->denormalizer->denormalize($data['specialisationCourseContent'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SpecialisationCourseContent::class, 'json', $context));
            unset($data['specialisationCourseContent']);
        }
        if (\array_key_exists('official', $data)) {
            $object->setOfficial($data['official']);
            unset($data['official']);
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
        $dataArray['id'] = $data->getId();
        $dataArray['schoolType'] = $data->getSchoolType();
        if ($data->isInitialized('subjectCode') && null !== $data->getSubjectCode()) {
            $dataArray['subjectCode'] = $data->getSubjectCode();
        }
        $dataArray['subjectName'] = $data->getSubjectName();
        if ($data->isInitialized('subjectDesignation') && null !== $data->getSubjectDesignation()) {
            $dataArray['subjectDesignation'] = $data->getSubjectDesignation();
        }
        if ($data->isInitialized('courseCode') && null !== $data->getCourseCode()) {
            $dataArray['courseCode'] = $data->getCourseCode();
        }
        if ($data->isInitialized('courseName') && null !== $data->getCourseName()) {
            $dataArray['courseName'] = $data->getCourseName();
        }
        if ($data->isInitialized('startSchoolYear') && null !== $data->getStartSchoolYear()) {
            $dataArray['startSchoolYear'] = $data->getStartSchoolYear();
        }
        if ($data->isInitialized('endSchoolYear') && null !== $data->getEndSchoolYear()) {
            $dataArray['endSchoolYear'] = $data->getEndSchoolYear();
        }
        if ($data->isInitialized('points') && null !== $data->getPoints()) {
            $dataArray['points'] = $data->getPoints();
        }
        if ($data->isInitialized('curriculum') && null !== $data->getCurriculum()) {
            $dataArray['curriculum'] = $data->getCurriculum();
        }
        if ($data->isInitialized('languageCode') && null !== $data->getLanguageCode()) {
            $dataArray['languageCode'] = $data->getLanguageCode();
        }
        if ($data->isInitialized('specialisationCourseContent') && null !== $data->getSpecialisationCourseContent()) {
            $dataArray['specialisationCourseContent'] = $this->normalizer->normalize($data->getSpecialisationCourseContent(), 'json', $context);
        }
        $dataArray['official'] = $data->getOfficial();
        foreach ($data as $key => $value) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus::class => false];
    }
}