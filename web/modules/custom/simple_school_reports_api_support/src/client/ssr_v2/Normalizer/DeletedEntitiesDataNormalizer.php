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
class DeletedEntitiesDataNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntitiesData::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntitiesData::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntitiesData();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('absences', $data)) {
            $values = [];
            foreach ($data['absences'] as $value) {
                $values[] = $value;
            }
            $object->setAbsences($values);
            unset($data['absences']);
        }
        if (\array_key_exists('attendanceEvents', $data)) {
            $values_1 = [];
            foreach ($data['attendanceEvents'] as $value_1) {
                $values_1[] = $value_1;
            }
            $object->setAttendanceEvents($values_1);
            unset($data['attendanceEvents']);
        }
        if (\array_key_exists('attendances', $data)) {
            $values_2 = [];
            foreach ($data['attendances'] as $value_2) {
                $values_2[] = $value_2;
            }
            $object->setAttendances($values_2);
            unset($data['attendances']);
        }
        if (\array_key_exists('grades', $data)) {
            $values_3 = [];
            foreach ($data['grades'] as $value_3) {
                $values_3[] = $value_3;
            }
            $object->setGrades($values_3);
            unset($data['grades']);
        }
        if (\array_key_exists('calendarEvents', $data)) {
            $values_4 = [];
            foreach ($data['calendarEvents'] as $value_4) {
                $values_4[] = $value_4;
            }
            $object->setCalendarEvents($values_4);
            unset($data['calendarEvents']);
        }
        if (\array_key_exists('attendanceSchedules', $data)) {
            $values_5 = [];
            foreach ($data['attendanceSchedules'] as $value_5) {
                $values_5[] = $value_5;
            }
            $object->setAttendanceSchedules($values_5);
            unset($data['attendanceSchedules']);
        }
        if (\array_key_exists('resources', $data)) {
            $values_6 = [];
            foreach ($data['resources'] as $value_6) {
                $values_6[] = $value_6;
            }
            $object->setResources($values_6);
            unset($data['resources']);
        }
        if (\array_key_exists('rooms', $data)) {
            $values_7 = [];
            foreach ($data['rooms'] as $value_7) {
                $values_7[] = $value_7;
            }
            $object->setRooms($values_7);
            unset($data['rooms']);
        }
        if (\array_key_exists('activitites', $data)) {
            $values_8 = [];
            foreach ($data['activitites'] as $value_8) {
                $values_8[] = $value_8;
            }
            $object->setActivitites($values_8);
            unset($data['activitites']);
        }
        if (\array_key_exists('duties', $data)) {
            $values_9 = [];
            foreach ($data['duties'] as $value_9) {
                $values_9[] = $value_9;
            }
            $object->setDuties($values_9);
            unset($data['duties']);
        }
        if (\array_key_exists('placements', $data)) {
            $values_10 = [];
            foreach ($data['placements'] as $value_10) {
                $values_10[] = $value_10;
            }
            $object->setPlacements($values_10);
            unset($data['placements']);
        }
        if (\array_key_exists('studyPlans', $data)) {
            $values_11 = [];
            foreach ($data['studyPlans'] as $value_11) {
                $values_11[] = $value_11;
            }
            $object->setStudyPlans($values_11);
            unset($data['studyPlans']);
        }
        if (\array_key_exists('programmes', $data)) {
            $values_12 = [];
            foreach ($data['programmes'] as $value_12) {
                $values_12[] = $value_12;
            }
            $object->setProgrammes($values_12);
            unset($data['programmes']);
        }
        if (\array_key_exists('syllabuses', $data)) {
            $values_13 = [];
            foreach ($data['syllabuses'] as $value_13) {
                $values_13[] = $value_13;
            }
            $object->setSyllabuses($values_13);
            unset($data['syllabuses']);
        }
        if (\array_key_exists('schoolUnitOfferings', $data)) {
            $values_14 = [];
            foreach ($data['schoolUnitOfferings'] as $value_14) {
                $values_14[] = $value_14;
            }
            $object->setSchoolUnitOfferings($values_14);
            unset($data['schoolUnitOfferings']);
        }
        if (\array_key_exists('groups', $data)) {
            $values_15 = [];
            foreach ($data['groups'] as $value_15) {
                $values_15[] = $value_15;
            }
            $object->setGroups($values_15);
            unset($data['groups']);
        }
        if (\array_key_exists('persons', $data)) {
            $values_16 = [];
            foreach ($data['persons'] as $value_16) {
                $values_16[] = $value_16;
            }
            $object->setPersons($values_16);
            unset($data['persons']);
        }
        if (\array_key_exists('organisations', $data)) {
            $values_17 = [];
            foreach ($data['organisations'] as $value_17) {
                $values_17[] = $value_17;
            }
            $object->setOrganisations($values_17);
            unset($data['organisations']);
        }
        foreach ($data as $key => $value_18) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value_18;
            }
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        if ($data->isInitialized('absences') && null !== $data->getAbsences()) {
            $values = [];
            foreach ($data->getAbsences() as $value) {
                $values[] = $value;
            }
            $dataArray['absences'] = $values;
        }
        if ($data->isInitialized('attendanceEvents') && null !== $data->getAttendanceEvents()) {
            $values_1 = [];
            foreach ($data->getAttendanceEvents() as $value_1) {
                $values_1[] = $value_1;
            }
            $dataArray['attendanceEvents'] = $values_1;
        }
        if ($data->isInitialized('attendances') && null !== $data->getAttendances()) {
            $values_2 = [];
            foreach ($data->getAttendances() as $value_2) {
                $values_2[] = $value_2;
            }
            $dataArray['attendances'] = $values_2;
        }
        if ($data->isInitialized('grades') && null !== $data->getGrades()) {
            $values_3 = [];
            foreach ($data->getGrades() as $value_3) {
                $values_3[] = $value_3;
            }
            $dataArray['grades'] = $values_3;
        }
        if ($data->isInitialized('calendarEvents') && null !== $data->getCalendarEvents()) {
            $values_4 = [];
            foreach ($data->getCalendarEvents() as $value_4) {
                $values_4[] = $value_4;
            }
            $dataArray['calendarEvents'] = $values_4;
        }
        if ($data->isInitialized('attendanceSchedules') && null !== $data->getAttendanceSchedules()) {
            $values_5 = [];
            foreach ($data->getAttendanceSchedules() as $value_5) {
                $values_5[] = $value_5;
            }
            $dataArray['attendanceSchedules'] = $values_5;
        }
        if ($data->isInitialized('resources') && null !== $data->getResources()) {
            $values_6 = [];
            foreach ($data->getResources() as $value_6) {
                $values_6[] = $value_6;
            }
            $dataArray['resources'] = $values_6;
        }
        if ($data->isInitialized('rooms') && null !== $data->getRooms()) {
            $values_7 = [];
            foreach ($data->getRooms() as $value_7) {
                $values_7[] = $value_7;
            }
            $dataArray['rooms'] = $values_7;
        }
        if ($data->isInitialized('activitites') && null !== $data->getActivitites()) {
            $values_8 = [];
            foreach ($data->getActivitites() as $value_8) {
                $values_8[] = $value_8;
            }
            $dataArray['activitites'] = $values_8;
        }
        if ($data->isInitialized('duties') && null !== $data->getDuties()) {
            $values_9 = [];
            foreach ($data->getDuties() as $value_9) {
                $values_9[] = $value_9;
            }
            $dataArray['duties'] = $values_9;
        }
        if ($data->isInitialized('placements') && null !== $data->getPlacements()) {
            $values_10 = [];
            foreach ($data->getPlacements() as $value_10) {
                $values_10[] = $value_10;
            }
            $dataArray['placements'] = $values_10;
        }
        if ($data->isInitialized('studyPlans') && null !== $data->getStudyPlans()) {
            $values_11 = [];
            foreach ($data->getStudyPlans() as $value_11) {
                $values_11[] = $value_11;
            }
            $dataArray['studyPlans'] = $values_11;
        }
        if ($data->isInitialized('programmes') && null !== $data->getProgrammes()) {
            $values_12 = [];
            foreach ($data->getProgrammes() as $value_12) {
                $values_12[] = $value_12;
            }
            $dataArray['programmes'] = $values_12;
        }
        if ($data->isInitialized('syllabuses') && null !== $data->getSyllabuses()) {
            $values_13 = [];
            foreach ($data->getSyllabuses() as $value_13) {
                $values_13[] = $value_13;
            }
            $dataArray['syllabuses'] = $values_13;
        }
        if ($data->isInitialized('schoolUnitOfferings') && null !== $data->getSchoolUnitOfferings()) {
            $values_14 = [];
            foreach ($data->getSchoolUnitOfferings() as $value_14) {
                $values_14[] = $value_14;
            }
            $dataArray['schoolUnitOfferings'] = $values_14;
        }
        if ($data->isInitialized('groups') && null !== $data->getGroups()) {
            $values_15 = [];
            foreach ($data->getGroups() as $value_15) {
                $values_15[] = $value_15;
            }
            $dataArray['groups'] = $values_15;
        }
        if ($data->isInitialized('persons') && null !== $data->getPersons()) {
            $values_16 = [];
            foreach ($data->getPersons() as $value_16) {
                $values_16[] = $value_16;
            }
            $dataArray['persons'] = $values_16;
        }
        if ($data->isInitialized('organisations') && null !== $data->getOrganisations()) {
            $values_17 = [];
            foreach ($data->getOrganisations() as $value_17) {
                $values_17[] = $value_17;
            }
            $dataArray['organisations'] = $values_17;
        }
        foreach ($data as $key => $value_18) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value_18;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntitiesData::class => false];
    }
}