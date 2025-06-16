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
class GradeNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grade::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grade::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grade();
        if (\array_key_exists('finalGrade', $data) && \is_int($data['finalGrade'])) {
            $data['finalGrade'] = (bool) $data['finalGrade'];
        }
        if (\array_key_exists('trial', $data) && \is_int($data['trial'])) {
            $data['trial'] = (bool) $data['trial'];
        }
        if (\array_key_exists('converted', $data) && \is_int($data['converted'])) {
            $data['converted'] = (bool) $data['converted'];
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
        if (\array_key_exists('student', $data)) {
            $object->setStudent($data['student']);
            unset($data['student']);
        }
        if (\array_key_exists('organisation', $data)) {
            $object->setOrganisation($data['organisation']);
            unset($data['organisation']);
        }
        if (\array_key_exists('registeredBy', $data)) {
            $object->setRegisteredBy($data['registeredBy']);
            unset($data['registeredBy']);
        }
        if (\array_key_exists('gradingTeacher', $data)) {
            $object->setGradingTeacher($data['gradingTeacher']);
            unset($data['gradingTeacher']);
        }
        if (\array_key_exists('group', $data)) {
            $object->setGroup($data['group']);
            unset($data['group']);
        }
        if (\array_key_exists('registeredDate', $data)) {
            $object->setRegisteredDate(\DateTime::createFromFormat('Y-m-d', $data['registeredDate'])->setTime(0, 0, 0));
            unset($data['registeredDate']);
        }
        if (\array_key_exists('gradeValue', $data)) {
            $object->setGradeValue($data['gradeValue']);
            unset($data['gradeValue']);
        }
        if (\array_key_exists('finalGrade', $data)) {
            $object->setFinalGrade($data['finalGrade']);
            unset($data['finalGrade']);
        }
        if (\array_key_exists('trial', $data)) {
            $object->setTrial($data['trial']);
            unset($data['trial']);
        }
        if (\array_key_exists('adaptedStudyPlan', $data)) {
            $object->setAdaptedStudyPlan($data['adaptedStudyPlan']);
            unset($data['adaptedStudyPlan']);
        }
        if (\array_key_exists('remark', $data)) {
            $object->setRemark($data['remark']);
            unset($data['remark']);
        }
        if (\array_key_exists('converted', $data)) {
            $object->setConverted($data['converted']);
            unset($data['converted']);
        }
        if (\array_key_exists('correctionType', $data)) {
            $object->setCorrectionType($data['correctionType']);
            unset($data['correctionType']);
        }
        if (\array_key_exists('semester', $data)) {
            $object->setSemester($data['semester']);
            unset($data['semester']);
        }
        if (\array_key_exists('year', $data)) {
            $object->setYear($data['year']);
            unset($data['year']);
        }
        if (\array_key_exists('syllabus', $data)) {
            $object->setSyllabus($this->denormalizer->denormalize($data['syllabus'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SyllabusReference::class, 'json', $context));
            unset($data['syllabus']);
        }
        if (\array_key_exists('diplomaProject', $data)) {
            $object->setDiplomaProject($this->denormalizer->denormalize($data['diplomaProject'], \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GradeDiplomaProject::class, 'json', $context));
            unset($data['diplomaProject']);
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
        $dataArray['student'] = $data->getStudent();
        if ($data->isInitialized('organisation') && null !== $data->getOrganisation()) {
            $dataArray['organisation'] = $data->getOrganisation();
        }
        $dataArray['registeredBy'] = $data->getRegisteredBy();
        if ($data->isInitialized('gradingTeacher') && null !== $data->getGradingTeacher()) {
            $dataArray['gradingTeacher'] = $data->getGradingTeacher();
        }
        if ($data->isInitialized('group') && null !== $data->getGroup()) {
            $dataArray['group'] = $data->getGroup();
        }
        $dataArray['registeredDate'] = $data->getRegisteredDate()?->format('Y-m-d');
        $dataArray['gradeValue'] = $data->getGradeValue();
        $dataArray['finalGrade'] = $data->getFinalGrade();
        if ($data->isInitialized('trial') && null !== $data->getTrial()) {
            $dataArray['trial'] = $data->getTrial();
        }
        $dataArray['adaptedStudyPlan'] = $data->getAdaptedStudyPlan();
        if ($data->isInitialized('remark') && null !== $data->getRemark()) {
            $dataArray['remark'] = $data->getRemark();
        }
        if ($data->isInitialized('converted') && null !== $data->getConverted()) {
            $dataArray['converted'] = $data->getConverted();
        }
        if ($data->isInitialized('correctionType') && null !== $data->getCorrectionType()) {
            $dataArray['correctionType'] = $data->getCorrectionType();
        }
        if ($data->isInitialized('semester') && null !== $data->getSemester()) {
            $dataArray['semester'] = $data->getSemester();
        }
        if ($data->isInitialized('year') && null !== $data->getYear()) {
            $dataArray['year'] = $data->getYear();
        }
        $dataArray['syllabus'] = $this->normalizer->normalize($data->getSyllabus(), 'json', $context);
        if ($data->isInitialized('diplomaProject') && null !== $data->getDiplomaProject()) {
            $dataArray['diplomaProject'] = $this->normalizer->normalize($data->getDiplomaProject(), 'json', $context);
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
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grade::class => false];
    }
}