<?php

namespace Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer;

use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Normalizer\CheckArray;
use Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Normalizer\ValidatorTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
class JaneObjectNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    protected $normalizers = [
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\OrganisationNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Person::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duty::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DutyNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Placement::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PlacementNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Group::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Programme::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ProgrammeNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SyllabusNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitOffering::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SchoolUnitOfferingNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlan::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\StudyPlanNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activity::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ActivityNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvent::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CalendarEventNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvent::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceEventNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceScheduleNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absence::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AbsenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendance::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AggregatedAttendanceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grade::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GradeNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Resource::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ResourceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Room::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\RoomNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Subscription::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SubscriptionNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ErrorNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisations::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\OrganisationsNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsExpanded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonsExpandedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpanded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonExpandedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Placements::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PlacementsNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementExpanded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PlacementExpandedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duties::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DutiesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyExpanded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DutyExpandedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\IdLookupNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupsExpanded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupsExpandedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpanded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupExpandedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupFragment::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupFragmentNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Programmes::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ProgrammesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlans::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\StudyPlansNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabuses::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SyllabusesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitOfferings::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SchoolUnitOfferingsNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activities::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ActivitiesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpanded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ActivityExpandedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvents::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CalendarEventsNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendances::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendancesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvents::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceEventsNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedules::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceSchedulesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grades::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GradesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absences::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AbsencesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendances::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AggregatedAttendancesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Resources::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ResourcesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Rooms::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\RoomsNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Subscriptions::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SubscriptionsNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CreateSubscription::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CreateSubscriptionNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\LogEntry::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\LogEntryNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StatisticsEntry::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\StatisticsEntryNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntities::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DeletedEntitiesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Meta::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\MetaNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\OrganisationReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ObjectReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ObjectReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Enrolment::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\EnrolmentNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SchoolUnitReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ProgrammeReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ProgrammeReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SyllabusReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SyllabusReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DutyReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ActivityReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CalendarEventReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementReference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PlacementReferenceNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationsLookupPostRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\OrganisationsLookupPostRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsLookupPostRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonsLookupPostRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementsLookupPostRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PlacementsLookupPostRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivitiesLookupPostRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ActivitiesLookupPostRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventsLookupPostRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CalendarEventsLookupPostRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendancesLookupPostRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendancesLookupPostRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEventsLookupPostRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceEventsLookupPostRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleLookupPostRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceScheduleLookupPostRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SubscriptionsGetRequest::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SubscriptionsGetRequestNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationAddress::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\OrganisationAddressNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ContactInfo::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ContactInfoNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ExternalIdentifier::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ExternalIdentifierNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonCivicNo::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonCivicNoNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Email::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\EmailNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Phonenumber::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PhonenumberNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonAddressesInner::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonAddressesInnerNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonResponsiblesInner::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonResponsiblesInnerNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyAssignmentRoleInner::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DutyAssignmentRoleInnerNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupMembership::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupMembershipNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupAllOf::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupAllOfNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ProgrammeContentInner::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ProgrammeContentInnerNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SpecialisationCourseContent::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SpecialisationCourseContentNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlanSyllabus::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\StudyPlanSyllabusNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlanContent::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\StudyPlanContentNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlanNotes::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\StudyPlanNotesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyAssignment::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DutyAssignmentNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudentException::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\StudentExceptionNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\TeacherException::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\TeacherExceptionNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventRoomsInner::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CalendarEventRoomsInnerNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventResourcesInner::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CalendarEventResourcesInnerNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventEmbedded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CalendarEventEmbeddedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEventEmbedded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceEventEmbeddedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleState::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceScheduleStateNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleEntry::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AttendanceScheduleEntryNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendanceEmbedded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\AggregatedAttendanceEmbeddedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GradeDiplomaProject::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GradeDiplomaProjectNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SubscriptionAllOf::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\SubscriptionAllOfNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbeddedGroupMemberships::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonExpandedAllOfEmbeddedGroupMembershipsNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbedded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonExpandedAllOfEmbeddedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOf::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonExpandedAllOfNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementExpandedAllOfEmbedded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PlacementExpandedAllOfEmbeddedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementExpandedAllOf::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PlacementExpandedAllOfNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyExpandedAllOfEmbedded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DutyExpandedAllOfEmbeddedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyExpandedAllOf::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DutyExpandedAllOfNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpandedAllOfEmbeddedAssignmentRoles::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupExpandedAllOfEmbeddedAssignmentRolesNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpandedAllOfEmbedded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupExpandedAllOfEmbeddedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpandedAllOf::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\GroupExpandedAllOfNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpandedAllOfEmbedded::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ActivityExpandedAllOfEmbeddedNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpandedAllOf::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\ActivityExpandedAllOfNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CreateSubscriptionResourceTypesInner::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\CreateSubscriptionResourceTypesInnerNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntitiesData::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\DeletedEntitiesDataNormalizer::class,
        
        \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonReference1::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Normalizer\PersonReference1Normalizer::class,
        
        \Jane\Component\JsonSchemaRuntime\Reference::class => \Drupal\simple_school_reports_api_support\client\ssr_v2\Runtime\Normalizer\ReferenceNormalizer::class,
    ], $normalizersCache = [];
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return array_key_exists($type, $this->normalizers);
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && array_key_exists(get_class($data), $this->normalizers);
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalizerClass = $this->normalizers[get_class($data)];
        $normalizer = $this->getNormalizer($normalizerClass);
        return $normalizer->normalize($data, $format, $context);
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $denormalizerClass = $this->normalizers[$type];
        $denormalizer = $this->getNormalizer($denormalizerClass);
        return $denormalizer->denormalize($data, $type, $format, $context);
    }
    private function getNormalizer(string $normalizerClass)
    {
        return $this->normalizersCache[$normalizerClass] ?? $this->initNormalizer($normalizerClass);
    }
    private function initNormalizer(string $normalizerClass)
    {
        $normalizer = new $normalizerClass();
        $normalizer->setNormalizer($this->normalizer);
        $normalizer->setDenormalizer($this->denormalizer);
        $this->normalizersCache[$normalizerClass] = $normalizer;
        return $normalizer;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [
            
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisation::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Person::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duty::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Placement::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Group::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Programme::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabus::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitOffering::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlan::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activity::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvent::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendance::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvent::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedule::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absence::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendance::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grade::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Resource::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Room::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Subscription::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Error::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Organisations::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsExpanded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpanded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Placements::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementExpanded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Duties::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyExpanded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\IdLookup::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupsExpanded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpanded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupFragment::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Programmes::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlans::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Syllabuses::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitOfferings::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Activities::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpanded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEvents::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Attendances::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEvents::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceSchedules::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Grades::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Absences::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendances::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Resources::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Rooms::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Subscriptions::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CreateSubscription::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\LogEntry::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StatisticsEntry::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntities::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Meta::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ObjectReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Enrolment::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SchoolUnitReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ProgrammeReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SyllabusReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementReference::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationsLookupPostRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonsLookupPostRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementsLookupPostRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivitiesLookupPostRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventsLookupPostRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendancesLookupPostRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEventsLookupPostRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleLookupPostRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SubscriptionsGetRequest::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\OrganisationAddress::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ContactInfo::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ExternalIdentifier::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonCivicNo::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Email::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\Phonenumber::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonAddressesInner::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonResponsiblesInner::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyAssignmentRoleInner::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupMembership::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupAllOf::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ProgrammeContentInner::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SpecialisationCourseContent::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlanSyllabus::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlanContent::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudyPlanNotes::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyAssignment::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\StudentException::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\TeacherException::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventRoomsInner::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventResourcesInner::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CalendarEventEmbedded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceEventEmbedded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleState::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AttendanceScheduleEntry::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\AggregatedAttendanceEmbedded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GradeDiplomaProject::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\SubscriptionAllOf::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbeddedGroupMemberships::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOfEmbedded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonExpandedAllOf::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementExpandedAllOfEmbedded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PlacementExpandedAllOf::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyExpandedAllOfEmbedded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DutyExpandedAllOf::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpandedAllOfEmbeddedAssignmentRoles::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpandedAllOfEmbedded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\GroupExpandedAllOf::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpandedAllOfEmbedded::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\ActivityExpandedAllOf::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\CreateSubscriptionResourceTypesInner::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\DeletedEntitiesData::class => false,
            \Drupal\simple_school_reports_api_support\client\ssr_v2\Model\PersonReference1::class => false,
            \Jane\Component\JsonSchemaRuntime\Reference::class => false,
        ];
    }
}