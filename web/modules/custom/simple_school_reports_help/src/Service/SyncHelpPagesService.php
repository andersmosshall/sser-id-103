<?php

namespace Drupal\simple_school_reports_help\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_module_info\Events\GetHelpPagesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SyncHelpPagesService
 */
class SyncHelpPagesService implements SyncHelpPagesServiceInterface, EventSubscriberInterface {
  use StringTranslationTrait;

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  protected function getMap(): array {
    $map = [
      // Students.
      [
        'title' => 'Elevlistan och massåtgärder',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom elevlistan och hur man arbetar med massåtgärder.',
        'vimeo' => 'https://vimeo.com/683903903',
        'context' => ['view.students.students', 'view.students.contact_info', '/admin/people'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Vikten av att ta bort elever och vårdnadshavare',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hur man tar bort elever och vårdnadshavare samt varför det är viktigt.',
        'vimeo' => 'https://vimeo.com/689931491',
        'context' => ['view.students.students', 'view.students.contact_info', '/admin/people'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Lägg till/hantera elever och vårdnadshavare',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hur man lägger till och hanterar elever och vårdnadshavare.',
        'vimeo' => 'https://vimeo.com/683903943',
        'context' => ['view.students.students', 'view.students.contact_info', 'view.caregivers.caregivers', 'user:student', 'user:caregiver'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Massåtgärder för vårdnadshavare',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hur massuppdaterar vårdnadshavare.',
        'vimeo' => 'https://vimeo.com/683904017',
        'context' => ['view.students.students', 'view.caregivers.caregivers', 'user:caregiver'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      // Admin
      [
        'title' => 'Lägg till/hantera lärare och andra användare',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom hur man lägger till och hanterar lärare och andra användare. På slutet av denna film visas hur man i efterhand kan skicka ut inloggningsinstruktioner, t.ex. till nya användare som aldrig loggat in tidigare.',
        'vimeo' => 'https://vimeo.com/683903991',
        'context' => ['/admin/people'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Rollbeskrivningar',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom roller och vad de innebär.',
        'vimeo' => 'https://vimeo.com/683904061',
        'context' => ['/admin/people', 'entity.user.canonical', 'entity.user.edit_form', 'user' ],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Exportera elever eller andra användare',
        'roles' => ['administrator'],
        'body' => 'Denna film går igenom hur man kan exportera användare till andra system.',
        'vimeo' => 'https://vimeo.com/964116593',
        'context' => ['view.students.students', 'view.students.contact_info', '/admin/people'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy', 'simple_school_reports_pmo_export', 'simple_school_reports_prorenata_export', 'simple_school_reports_skolon_export'],
      ],
      // Courses
      [
        'title' => 'Kurser och kurslistan',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom kurser och kurslistan.',
        'vimeo' => 'https://vimeo.com/687701455',
        'context' => ['node:course', 'node:course_attendance_report', 'view.courses.my_courses', 'view.courses.all_courses'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Närvaro/frånvaro för kurs',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom närvaro/frånvaro för kurser.',
        'vimeo' => 'https://vimeo.com/687701437',
        'context' => ['node:course', 'node:course_attendance_report', 'view.courses.my_courses', 'view.courses.all_courses'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Schema för kurs',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom hantering av schema för kurs.',
        'vimeo' => 'https://vimeo.com/1053720955',
        'context' => ['node:course', 'view.courses.my_courses', 'view.courses.all_courses'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy', 'simple_school_reports_schema_ssr'],
      ],
      // Day absence
      [
        'title' => 'Dagsfrånvaro',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom hur dagfrånvaro registreras av skolpersonal.',
        'vimeo' => 'https://vimeo.com/687701413',
        'context' => ['view.students.students', 'user:student'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Dagsfrånvaro',
        'roles' => ['caregiver',],
        'body' => 'Denna film går igenom hur dagfrånvaro registreras av vårdnadshavare.',
        'vimeo' => 'https://vimeo.com/687701398',
        'context' => ['user:student'],
        'module' => ['simple_school_reports_caregiver_login'],
      ],
      [
        'title' => 'Veckosammanställning av frånvaro och EHT-stöd',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hur man tar fram statistik för frånvaro veckovis och hur det kan vara ett stöd för skolans EHT-arbete.',
        'vimeo' => 'https://vimeo.com/687701381',
        'context' => ['simple_school_reports_core.weekly_summary', 'user:student', 'simple_school_reports_core.statistics'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      // Make up time
      [
        'title' => 'Konceptet bonustimme - skapa bonustimme',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hur man skapar bonustimmekurs.',
        'vimeo' => 'https://vimeo.com/687701340',
        'context' => ['node:course', 'node:course_attendance_report', 'view.courses.my_courses', 'view.courses.all_courses'],
        'module' => ['simple_school_reports_absence_make_up'],
      ],
      [
        'title' => 'Konceptet bonustimme - påminn om bonustimme',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom konceptet bonustimme för påminnelse av bonustimme.',
        'vimeo' => 'https://vimeo.com/687701360',
        'context' => ['view.students.students', 'view.make_up_time_reminder.make_up_time_reminder',],
        'module' => ['simple_school_reports_absence_make_up'],
      ],
      [
        'title' => 'Konceptet bonustimme - registrera bonustid',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hur man registerar bonustid för konceptet bonustimme.',
        'vimeo' => 'https://vimeo.com/687701322',
        'context' => ['node:course', 'node:course_attendance_report', 'view.courses.my_courses', 'view.courses.all_courses'],
        'module' => ['simple_school_reports_absence_make_up'],
      ],
      // User
      [
        'title' => 'Byt lösenord och redigera användare',
        'body' => 'Denna film går igenom hur lösenord eller annan information för användare ändras.',
        'vimeo' => 'https://vimeo.com/688778696',
        'context' => ['entity.user.canonical', 'entity.user.edit_form',],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Statistik för elev',
        'roles' => ['administrator', 'principle', 'teacher', 'caregiver',],
        'body' => 'Denna film går igenom vad du kan se för statistik för elever.',
        'vimeo' => 'https://vimeo.com/688778670',
        'context' => ['user:student',],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy', 'simple_school_reports_grade_stats', 'simple_school_reports_iup', 'simple_school_reports_reviews'],
      ],
      // Admin menu
      [
        'title' => 'Administration av ämnen och språkämnen',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration av ämnen.',
        'vimeo' => 'https://vimeo.com/688778643',
        'context' => ['simple_school_reports_core.admin', 'view.school_subjects.school_subjects', ],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      [
        'title' => 'Administration av meddelanden',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration av meddelanden.',
        'vimeo' => 'https://vimeo.com/688778606',
        'context' => ['simple_school_reports_core.admin', 'simple_school_reports_core.config_message_templates', ],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy', 'simple_school_reports_absence_make_up'],
      ],
      [
        'title' => 'Administration av startsida',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration av startsida.',
        'vimeo' => 'https://vimeo.com/688778581',
        'context' => ['simple_school_reports_core.admin', 'simple_school_reports_core.config_start_page_content', ],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy', 'simple_school_reports_caregiver_login'],
      ],
      [
        'title' => 'Administration av standardfraser för skriftliga omdömen',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration av standardfraser för skriftliga omdömen.',
        'vimeo' => 'https://vimeo.com/688778550',
        'context' => ['simple_school_reports_core.admin', 'view.standard_phrases.written_reviews', 'term:written_reviews_standard_phrase',],
        'module' => ['simple_school_reports_reviews'],
      ],
      [
        'title' => 'Administration av standardfraser för IUP',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration av standardfraser för IUP.',
        'vimeo' => 'https://vimeo.com/688778531',
        'context' => ['simple_school_reports_core.admin', 'view.standard_phrases.iup', 'term:iup_standard_phrase'],
        'module' => ['simple_school_reports_iup'],
      ],
      [
        'title' => 'Administration av standardmål för IUP',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration av standardmål för IUP.',
        'vimeo' => 'https://vimeo.com/688778498',
        'context' => ['simple_school_reports_core.admin', 'view.iup_standard_goals.iup_standard_goals', 'term:iup_standard_phrase', 'term:iup_standard_goal'],
        'module' => ['simple_school_reports_iup'],
      ],
      // Grade round
      [
        'title' => 'Administration av betygsregistreringar/betygsomgångar',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration och utskrift av betygsregistreringar och betygsomgångar.',
        'vimeo' => 'https://vimeo.com/689931534',
        'context' => ['node:grade_round', 'view.grade_registration_rounds.active', 'simple_school_reports_grade_registration.generate_grade_catalog'],
        'module' => ['simple_school_reports_grade_registration'],
      ],
      [
        'title' => 'Registrera betyg',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom hur betyg registreras.',
        'vimeo' => 'https://vimeo.com/689931523',
        'context' => ['node:grade_round', 'node:grade_subject', 'view.grade_registration_rounds.active', 'view.grade_registration_subject_list.grade_registration_subject_list'],
        'module' => ['simple_school_reports_grade_registration'],
      ],
      [
        'title' => 'Registrera sambedömning av betyg',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom hur sambedömda betyg registreras.',
        'vimeo' => 'https://vimeo.com/961999010',
        'context' => ['node:grade_round', 'node:grade_subject', 'view.grade_registration_rounds.active', 'view.grade_registration_subject_list.grade_registration_subject_list'],
        'module' => ['simple_school_reports_grade_registration'],
      ],
      [
        'title' => 'Betygsstatistik',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom hur man tar fram statistik för betygsregistreringar.',
        'vimeo' => 'https://vimeo.com/689931502',
        'context' => ['view.grade_statistics.list', 'node:grade_statistics', 'simple_school_reports_core.statistics'],
        'module' => ['simple_school_reports_grade_stats'],
      ],
      [
        'title' => 'Registrera godkända/ej godkända omdömen',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom hur betyg i form av godkända/ej godkända omdömen registreras.',
        'vimeo' => 'https://vimeo.com/689931515',
        'context' => ['node:grade_round', 'node:grade_subject', 'view.grade_registration_rounds.active', 'view.grade_registration_subject_list.grade_registration_subject_list', 'view.grade_registration_subject_list.grade_registration_subject_list'],
        'module' => ['simple_school_reports_geg_grade_registration'],
      ],
      // IUP round
      [
        'title' => 'Administration av IUP/IUP-omgångar',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration och utskrift av IUP.',
        'vimeo' => 'https://vimeo.com/689931479',
        'context' => ['node:iup_round', 'view.iup_rounds.list', 'view.iup_student_list.iup_student_list',],
        'module' => ['simple_school_reports_iup'],
      ],
      [
        'title' => 'Registrera IUP',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom hantering av IUP.',
        'vimeo' => 'https://vimeo.com/689931468',
        'context' => ['node:iup', 'view.iup_rounds.list', 'view.iup_student_list.iup_student_list',],
        'module' => ['simple_school_reports_iup'],
      ],
      [
        'title' => 'Hantera IUP-mål',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom hantering av IUP-mål.',
        'vimeo' => 'https://vimeo.com/689931461',
        'context' => ['node:iup_goal', 'view.iup_goals.list', 'node:iup', ],
        'module' => ['simple_school_reports_iup'],
      ],
      // SO
      [
        'title' => 'Administration av skriftliga omdömen',
        'roles' => ['administrator',],
        'body' => 'Denna film går igenom administration och utskrift av skriftliga omdömen.',
        'vimeo' => 'https://vimeo.com/689931451',
        'context' => ['node:written_reviews_round', 'view.written_reviews_rounds.list', 'view.written_reviews_subject_list.written_reviews_subject_list', 'view.written_reviews_student_list.written_reviews_student_list'],
        'module' => ['simple_school_reports_reviews'],
      ],
      [
        'title' => 'Registrera skriftliga omdömen - ämne',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom registrering av omdöme för ämne i skriftliga omdömen.',
        'vimeo' => 'https://vimeo.com/689931441',
        'context' => ['node:written_reviews_round', 'view.written_reviews_rounds.list', 'view.written_reviews_subject_list.written_reviews_subject_list', 'view.written_reviews_student_list.written_reviews_student_list'],
        'module' => ['simple_school_reports_reviews'],
      ],
      [
        'title' => 'Registrera skriftliga omdömen - skolans insatser',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom registrering av skolans insatser i skriftliga omdömen.',
        'vimeo' => 'https://vimeo.com/689931428',
        'context' => ['node:written_reviews_round', 'view.written_reviews_rounds.list', 'view.written_reviews_subject_list.written_reviews_subject_list', 'view.written_reviews_student_list.written_reviews_student_list'],
        'module' => ['simple_school_reports_reviews'],
      ],
      // List templates
      [
        'title' => 'Hantera och dela listmallar',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hantering och delning av listmallar.',
        'vimeo' => 'https://vimeo.com/689931413',
        'context' => ['node:list_template', 'view.list_template.list',],
        'module' => ['simple_school_reports_list_templates'],
      ],
      // Budget
      [
        'title' => 'Administration och utvärdering av budget',
        'roles' => ['budget_administrator'],
        'body' => 'Denna film går igenom hantering och administration av budget.',
        'vimeo' => 'https://vimeo.com/689931399',
        'context' => ['node:budget', 'view.budget.list',],
        'module' => ['simple_school_reports_budget'],
      ],
      [
        'title' => 'Granskning av budget',
        'roles' => ['budget_administrator', 'budget_reviewer'],
        'body' => 'Denna film går igenom granskning av budget.',
        'vimeo' => 'https://vimeo.com/689931387',
        'context' => ['node:budget', 'view.budget.list',],
        'module' => ['simple_school_reports_budget'],
      ],
      // Terms
      [
        'title' => 'Terminer',
        'roles' => ['administrator'],
        'body' => 'Denna film går igenom hur man administrerar terminer.',
        'vimeo' => 'https://vimeo.com/682234848',
        'context' => ['/admin/config-simple-school-reports', '/admin/config-simple-school-reports/terms', 'term:term'],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy'],
      ],
      // Special diet
      [
        'title' => 'Information om specialkost',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hantering av information om specialkost.',
        'vimeo' => 'https://vimeo.com/745550850',
        'context' => ['user:student', 'view.list_template.list', '/special-diet'],
        'module' => ['simple_school_reports_special_diet'],
      ],
      // Consents
      [
        'title' => 'Skapa och hantera samtycken',
        'roles' => ['administrator', 'principle'],
        'body' => 'Denna film går igenom hantering av samtycken.',
        'vimeo' => 'https://vimeo.com/870866034',
        'context' => ['view.consents.list', 'view.consents_per_user.reminder', '/consents'],
        'module' => ['simple_school_reports_consents']
      ],
      [
        'title' => 'Hantera användares samtycken',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hantering av samtycken för andra användar såsom efterregistering eller registera åt annan användare.',
        'vimeo' => 'https://vimeo.com/870866020',
        'context' => ['user', 'view.consents.list', 'view.consents_per_user.reminder', 'view.consents_per_user.list', '/consents'],
        'module' => ['simple_school_reports_consents']
      ],
      [
        'title' => 'Hantera egna samtycken',
        'roles' => ['administrator', 'principle', 'teacher', 'caregiver'],
        'body' => 'Denna film går igenom hantering av samtycken för andra användar såsom efterregistering eller registera åt annan användare.',
        'vimeo' => 'https://vimeo.com/870866004',
        'context' => ['user', '/start', '/start/caregiver', '/start/default'],
        'module' => ['simple_school_reports_consents']
      ],
      // Absence matrix
      [
        'title' => 'Frånvaromatris',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom frånvaro hantering i frånvaromatris.',
        'vimeo' => 'https://vimeo.com/873479281',
        'context' => ['/absence', '/absence-matrix', '/absence/history',],
        'module' => ['simple_school_reports_core_gr', 'simple_school_reports_core_gy', 'simple_school_reports_absence_matrix'],
      ],
      // Utvecklingssamtal.
      [
        'title' => 'Administrera utvecklingssamtal för elever',
        'roles' => ['administrator', 'principle'],
        'body' => 'Denna film går igenom hur du som administratör hanterar omgångar för utvecklingssamtal för elever.',
        'vimeo' => 'https://vimeo.com/899327677',
        'context' => ['/student-development-interview-rounds',],
        'module' => ['simple_school_reports_student_di']
      ],
      [
        'title' => 'Skapa och hantera utvecklingssamtal för elever',
        'roles' => ['teacher'],
        'body' => 'Denna film går igenom hur du som lärare skapar och hanterar utvecklingssamtal för elever.',
        'vimeo' => 'https://vimeo.com/899327692',
        'context' => ['/student-development-interview-rounds', 'student_di.meeting_series_create', 'view.student_development_interview_meetings.list'],
        'module' => ['simple_school_reports_student_di']
      ],
      [
        'title' => 'Hantera utvecklingssamtal',
        'roles' => ['teacher', 'caregiver'],
        'body' => 'Denna film går igenom hur du som vårdnadshavare bokar och avbokar utvecklingssamtal.',
        'vimeo' => 'https://vimeo.com/899327710',
        'context' => ['user', 'simple_school_reports_student_di.di_user_tab'],
        'module' => ['simple_school_reports_student_di']
      ],
      // Attendance statistics.
      [
        'title' => 'Hantera skolveckor för närvaroanalys',
        'roles' => ['administrator', 'principle'],
        'body' => 'Denna film går igenom hur du som administratör hanterar skolveckor för närvaroanalys.',
        'vimeo' => 'https://vimeo.com/903808733',
        'context' => ['user', 'simple_school_reports_extension_proxy.school_week_settings'],
        'module' => ['simple_school_reports_attendance_analyse']
      ],
      [
        'title' => 'Hantera avvikelse för skolveckor',
        'roles' => ['administrator', 'principle'],
        'body' => 'Denna film går igenom hur du som administratör hanterar avvikelser skolveckor för närvaroanalys.',
        'vimeo' => 'https://vimeo.com/946488255',
        'context' => ['user', 'simple_school_reports_student_di.di_user_tab', 'simple_school_reports_extension_proxy.school_week_settings'],
        'module' => ['simple_school_reports_attendance_analyse']
      ],
      [
        'title' => 'Kopiera skolveckor',
        'roles' => ['administrator', 'principle'],
        'body' => 'Denna film går igenom hur du som administratör kan kopiera skolveckor.',
        'vimeo' => 'https://vimeo.com/1040468222',
        'context' => ['simple_school_reports_extension_proxy.school_week_settings'],
        'module' => ['simple_school_reports_attendance_analyse']
      ],
      [
        'title' => 'Hantera anpassad studiegång för närvaroanalys',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hur du som skolpersonal hanterar anpassad studiegång för närvaroanalys.',
        'vimeo' => 'https://vimeo.com/903809075',
        'context' => ['user', 'simple_school_reports_student_di.di_user_tab', 'simple_school_reports_extension_proxy.school_week_settings'],
        'module' => ['simple_school_reports_attendance_analyse']
      ],
      [
        'title' => 'Se närvarostatistik',
        'roles' => ['administrator', 'principle', 'teacher'],
        'body' => 'Denna film går igenom hur du som skolpersonal hittar närvarostatistik, för enskilda elever och veckosammanställningar.',
        'vimeo' => 'https://vimeo.com/903805829',
        'context' => ['user', 'simple_school_reports_student_di.di_user_tab', 'simple_school_reports_extension_proxy.school_week_settings'],
        'module' => ['simple_school_reports_attendance_analyse']
      ],
      // Attendance statistics for a period
      [
        'title' => 'Se närvarostatistik för en period',
        'roles' => ['teacher', 'administrator', 'principle'],
        'body' => 'Denna film går igenom hur du som administratör hittar närvarostatistik över en period samt hur du ser sammanställningar för frånvaro utifrån procentuella grupperingar.',
        'vimeo' => 'https://vimeo.com/946488236',
        'context' => ['simple_school_reports_extension_proxy.school_week_settings', 'simple_school_reports_attendance_period_analyse.attendance_period_analyse_settings', '/statistics/attendance-period-analyse'],
        'module' => ['simple_school_reports_attendance_period_analyse']
      ],
      // Leave applications
      [
        'title' => 'Skapa och visa ledighetsansökan',
        'roles' => ['teacher', 'caregiver'],
        'body' => 'Denna film går igenom hur du som lärare eller vårdnadshavare skapar, visar och redigerar ledighetsansökningar.',
        'vimeo' => 'https://vimeo.com/1006742588',
        'context' => ['user', 'simple_school_reports_leave_application.leave_application_student_tab', 'simple_school_reports_leave_application.create'],
        'module' => ['simple_school_reports_leave_application']
      ],
      [
        'title' => 'Hantera ledighetsansökan',
        'roles' => ['teacher', 'principle'],
        'body' => 'Denna film går igenom hur du som lärare eller rektor hanterar ledighetsansökningar.',
        'vimeo' => 'https://vimeo.com/1006742630',
        'context' => ['simple_school_reports_leave_application.handle', 'view.pending_leave_applications.pending'],
        'module' => ['simple_school_reports_leave_application']
      ],
      [
        'title' => 'Inställningar för ledighetsansökningar',
        'roles' => ['administrator'],
        'body' => 'Denna film går igenom hur du som administratör hanterar inställningar för ledighetsansökningar.',
        'vimeo' => 'https://vimeo.com/1006742610',
        'context' => ['simple_school_reports_leave_application.student_leave_application_settings'],
        'module' => ['simple_school_reports_leave_application']
      ],
      // Extra adaptations.
      [
        'title' => 'Extra anpassningar',
        'roles' => ['administrator', 'teacher', 'principle'],
        'body' => 'Denna film går igenom hur extra anpassningar hanteras och används.',
        'vimeo' => 'https://vimeo.com/1040468338',
        'context' => ['user', 'view.extra_adaptations.list', 'view.students.extra_adaptations'],
        'module' => ['simple_school_reports_extra_adaptations']
      ],
      // Classes
      [
        'title' => 'Klasser och klasshantering',
        'roles' => ['administrator', 'teacher', 'principle'],
        'body' => 'Denna film går igenom hur klasser hanteras och används.',
        'vimeo' => 'https://vimeo.com/1040468253',
        'context' => ['view.classes_list.list', 'node:course', 'ssr_assessment_group'],
        'module' => ['simple_school_reports_class']
      ],
      // Examinations
      [
        'title' => 'Hantera bedömningsgrupper och examinationer',
        'roles' => ['administrator', 'teacher', 'principle'],
        'body' => 'Denna film går igenom hur bedömningsgrupper och examinationer hanteras.',
        'vimeo' => 'https://vimeo.com/1040468311',
        'context' => ['ssr_assessment_group', 'ssr_examination', 'ssr_examination_result', 'simple_school_reports_examinations.handle_examination_result', 'simple_school_reports_examinations.sort_out_examinations', 'view.assessment_groups.list'],
        'module' => ['simple_school_reports_examinations']
      ],
//      [
//        'title' => 'Kopiera kurs till bedömningsgrupp',
//        'roles' => ['administrator', 'teacher', 'principle'],
//        'body' => 'Denna film går igenom hur du kopierar en kurs till en bedömningsgrupp.',
//        'vimeo' => 'https://vimeo.com/1040468311', // Todo update link
//        'context' => ['ssr_assessment_group', 'node:course', 'simple_school_reports_examinations.course_to_assessment_group'],
//        'module' => ['simple_school_reports_examinations']
//      ],
      [
        'title' => 'Examinationslista på elevsidan',
        'roles' => ['teacher', 'caregiver'],
        'body' => 'Denna film går igenom hur du ser examinationslistan på elevsidan.',
        'vimeo' => 'https://vimeo.com/1040468290',
        'context' => ['user', 'simple_school_reports_examinations.examination_list_student_tab'],
        'module' => ['simple_school_reports_examinations']
      ],
      // SSR-schema
      [
        'title' => 'Att rapporter med SSR-schema',
        'roles' => ['administrator', 'teacher'],
        'body' => 'Denna film går igenom hur snabbt och enkelt kan rapporter eller ställa in lektioner med SSR-schema.',
        'vimeo' => 'https://vimeo.com/1053720905',
        'context' => ['node:course', 'view.courses.my_courses', 'view.courses.all_courses', 'view.calendar_events_courses.my_courses', 'view.calendar_events_courses.all_courses'],
        'module' => ['simple_school_reports_schema_ssr'],
      ],
      [
        'title' => 'Administration för SSR-schema',
        'roles' => ['administrator'],
        'body' => 'Denna film går igenom hur du administrerar funktioner som kommer med SSR-schema.',
        'vimeo' => 'https://vimeo.com/1053720930',
        'context' => ['node:course', 'view.courses.my_courses', 'view.courses.all_courses', 'view.calendar_events_courses.my_courses', 'view.calendar_events_courses.all_courses'],
        'module' => ['simple_school_reports_schema_ssr'],
      ],
    ];

    $formatted_map = [];
    foreach ($map as $weight => $help_page) {
      $system_id = $help_page['vimeo'];
      // Keep only numbers.
      $system_id = preg_replace('/[^0-9]/', '', $system_id);

      $is_ssr_promo = $this->moduleHandler->moduleExists('ssr_promo_core');

      $roles = [];
      foreach ($help_page['roles'] ?? [] as $role) {
        if ($is_ssr_promo) {
          $roles[] = $role;
          continue;
        }
        $roles[] = ['target_id' => $role];
      }

      $enabled_modules = 0;
      foreach ($help_page['module'] ?? [] as $module) {
        $enabled =  $this->moduleHandler->moduleExists($module);
        if ($enabled) {
          $enabled_modules++;
        }

        $core_modules = [
          'simple_school_reports_core',
          'simple_school_reports_core_gr',
          'simple_school_reports_core_gy',
        ];
        if (in_array($module, $core_modules) || $enabled) {
          $body_suffix = '';
          continue;
        }

        if (!$enabled) {
          $body_suffix = ' <strong>Kräver utökad modul.</strong>';
        }
      }

      $module_enabled_status = 'yes';
      if (count($help_page['module']) > 0) {
        if ($enabled_modules === 0) {
          $module_enabled_status = 'no';
        }
        elseif ($enabled_modules !== count($help_page['module'])) {
          $module_enabled_status = 'partial';
        }
      }

      $help_page['body'] .= $body_suffix;

      $formatted_map[$system_id] = [
        'title' => $help_page['title'],
        'field_system_id' => $system_id,
        'field_target_group' => $roles,
        'field_vimeo' => ['vimeo_url' => $help_page['vimeo']],
        'field_context' => $help_page['context'],
        'field_module' => $help_page['module'],
        'field_weight' => $weight,
        'field_module_enabled' => $module_enabled_status,
        'body' => [
          'value' => '<p>' . $help_page['body'] . '</p>',
          'format' => 'full_html',
        ],
      ];
    }

    return $formatted_map;
  }

  public function syncHelpPages(bool $force = FALSE): bool {
    try {
      $map = $this->getMap();
      $current_sync_hash = sha1(json_encode($map));

      if (!$force) {
        $last_sync_hash = $this->state->get('simple_school_reports_help.last_help_pages_sync_hash');
        if ($last_sync_hash === $current_sync_hash) {
          return TRUE;
        }
      }

      foreach ($map as $system_id  => $data) {
        $help_page = current($this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'help_page', 'field_system_id' => $system_id]));
        if (empty($help_page)) {
          $help_page = $this->entityTypeManager->getStorage('node')->create([
            'type' => 'help_page',
            'langcode' => 'sv',
          ]);
        }
        $help_page->set('uid', 1);

        foreach ($data as $field => $value) {
          if ($help_page->hasField($field)) {
            $help_page->set($field, $value);
          }
        }

        $help_page->save();
      }

      // Remove all module nodes that are not in the map.
      $ids_to_delete = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'help_page')
        ->condition('field_system_id', array_keys($map), 'NOT IN')
        ->execute();
      if (!empty($ids_to_delete)) {
        $this->entityTypeManager->getStorage('node')->delete($this->entityTypeManager->getStorage('node')->loadMultiple($ids_to_delete));
      }

      $this->state->set('simple_school_reports_help.last_help_pages_sync_hash', $current_sync_hash);
    } catch (\Exception $e) {
      return FALSE;
    }

    return TRUE;
  }

  public static function getSubscribedEvents() {
    $events['ssr_get_help_pages'][] = 'onSsrGetHelpPages';
    return $events;
  }

  public function onSsrGetHelpPages(GetHelpPagesEvent $event) {
    $result = $this->syncHelpPages();
    if (!$result) {
      throw new \Exception('Could not sync help pages.');
    }

    $help_pages = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'help_page']);
    foreach ($help_pages as $help_page) {
      $modules = array_column($help_page->get('field_module')->getValue(), 'value');
      foreach ($modules as $module) {
        $event->addHelpPageNid($module, $help_page->id());
      }
    }
  }

}
