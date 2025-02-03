<?php

namespace Drupal\simple_school_reports_schema_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\SchoolSubjectHelper;
use Drupal\simple_school_reports_core\Service\CourseServiceInterface;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\simple_school_reports_entities\CalendarEventInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\simple_school_reports_schema_support\Service\CalendarEventsSyncServiceInterface;
use Drupal\simple_school_reports_schema_support\Service\SchemaSupportServiceInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for sync course events.
 */
class SyncCourseEventForm extends ConfirmFormBase {

  /**
   * Constructs a new SyncCourseEventForm.
   */
  public function __construct(
    protected TermServiceInterface $termService,
    protected CourseServiceInterface $courseService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.term_service'),
      $container->get('simple_school_reports_core.course_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sync_course_event_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Sync lessons to report');
  }

  public function getCancelRoute() {
    return 'view.calendar_events_courses.my_courses';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Sync');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_term_start = $this->termService->getCurrentTermStart();
    $current_term_end = $this->termService->getCurrentTermEnd();

    $today = new \DateTime('today');

    $upper_limit = clone $today;
    $upper_limit->modify('+3 days');
    $upper_limit->setTime(23, 59, 59);

    if ($upper_limit->getTimestamp() > $current_term_end->getTimestamp()) {
      $upper_limit = $current_term_end;
    }

    $default_value = $today->getTimestamp() >= $current_term_start->getTimestamp() && $today->getTimestamp() <= $current_term_end->getTimestamp()
      ? $today
      : NULL;

    $form['sync_from'] = [
      '#title' => $this->t('Sync from'),
      '#type' => 'date',
      '#default_value' => $default_value?->format('Y-m-d'),
      '#min' => $current_term_start->format('Y-m-d'),
      '#max' => $upper_limit->format('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['sync_to'] = [
      '#title' => $this->t('Sync from'),
      '#type' => 'date',
      '#default_value' => $default_value?->format('Y-m-d'),
      '#min' => $current_term_start->format('Y-m-d'),
      '#max' => $upper_limit->format('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['info'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Lessons to report are synced every night for the current day. But on this page you can trigger a manual sync for a specific period. Not outside current active term though.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    $from_date_string = $form_state->getValue('sync_from');
    $to_date_string = $form_state->getValue('sync_to');
    if (!$from_date_string || !$to_date_string) {
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      return;
    }

    // Add course calendar events sync to queue.
    $course_ids_to_sync = $this->courseService->getActiveCourseIdsWithStudents();

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Sync lessons to report'),
      'init_message' => $this->t('Sync lessons to report'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
      'finished' => [self::class, 'finished'],
    ];

    $from_date = new \DateTime($from_date_string . ' 00:00:00');
    $to_date = new \DateTime($to_date_string . ' 23:59:59');

    if ($to_date < $from_date) {
      $temp = $from_date;
      $from_date = $to_date;
      $to_date = $temp;
    }

    $to_ts = $to_date->getTimestamp();

    $current_day = clone $from_date;
    $safe_limit = 0;
    while ($current_day->getTimestamp() <= $to_ts && $safe_limit < 365) {
      $current_day->setTime(0, 0, 0);
      $sync_from = $current_day->getTimestamp();
      $synt_to = $sync_from + 86399;

      foreach ($course_ids_to_sync as $course_id) {
        $batch['operations'][] = [[self::class, 'batchSyncCourseEvents'], [$course_id, $sync_from, $synt_to]];
      }

      $safe_limit++;
      $current_day->modify('+1 day');
    }

    if (!empty($batch['operations'])) {
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('There is no lessons to report to sync.'));
    }
  }

  public static function batchSyncCourseEvents(string|int $course_id, int $from, int $to, &$context): void {
    $from_date = new DrupalDateTime('@' . $from);
    $day = $from_date->format('Y-m-d');

    /** @var \Drupal\simple_school_reports_schema_support\Service\CalendarEventsSyncServiceInterface $calender_events_sync_service */
    $calender_events_sync_service = \Drupal::service('simple_school_reports_schema_support.calendar_events_sync');
    $calender_events_sync_service->syncCourseCalendarEvents($course_id, $from, $to, TRUE);
    $context['results']['synced_days'][$day] = TRUE;
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['synced_days'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    if (!empty($results['synced_days'])) {
      \Drupal::messenger()->addStatus(t('@count days has been synced with lessons to report.', ['@count'  => count($results['synced_days'])]));
    }
  }

  public static function access(AccountInterface $account) {
    if (!ssr_use_schema()) {
      return AccessResult::forbidden();
    }

    /** @var \Drupal\simple_school_reports_schema_support\Service\CalendarEventsSyncServiceInterface $calendar_events_sync_service */
    $calendar_events_sync_service = \Drupal::service('simple_school_reports_schema_support.calendar_events_sync');
    if (!$calendar_events_sync_service->syncIsEnabled()) {
      return AccessResult::forbidden()->addCacheTags(['ssr_calendar_event_list']);
    }

    /** @var \Drupal\simple_school_reports_core\Service\TermServiceInterface $term_service */
    $term_service = \Drupal::service('simple_school_reports_core.term_service');
    $active_term = $term_service->getCurrentTermStart(FALSE);
    if (!$active_term) {
      return AccessResult::forbidden()
        ->addCacheContexts(['current_day'])
        ->addCacheTags(['taxonomy_term_list:term']);
    }

    return AccessResult::allowedIfHasPermission($account, 'administer simple school reports settings')
      ->addCacheContexts(['current_day'])
      ->addCacheTags(['taxonomy_term_list:term']);
  }
}
