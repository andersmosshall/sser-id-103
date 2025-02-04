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
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_entities\CalendarEventInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\simple_school_reports_schema_support\Service\SchemaSupportServiceInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for fast report calendar event.
 */
class MultipleFastReportCourseEventForm extends ConfirmFormBase {

  /**
   * Constructs a new MailMultipleCaregiversForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected SchemaSupportServiceInterface $schemaSupportService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $connection,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('simple_school_reports_schema_support.schema_support'),
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiple_fast_report_course_event_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Fast report lessons');
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
    return $this->t('Fast report');
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
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL, ?CalendarEventInterface $ssr_calendar_event = NULL) {
    if ($ssr_calendar_event instanceof CalendarEventInterface) {
      $calendar_events = [$ssr_calendar_event];
    }
    else {
      // Retrieve the accounts to be canceled from the temp store.
      /** @var \Drupal\user\Entity\User[] $accounts */
      $calendar_events = $this->tempStoreFactory
        ->get('fast_report_multiple_course_events')
        ->get($this->currentUser()->id());
    }

    if (empty($calendar_events)) {
      return $this->redirect($this->getCancelRoute());
    }

    $form['info'] = [];
    $course_ids = [];
    $student_ids = [];
    $global_from = NULL;
    $global_to = NULL;

    $form['calendar_events'] = ['#tree' => TRUE];
    /** @var CalendarEventInterface $calendar_event */
    foreach ($calendar_events as $calendar_event) {
      if ($calendar_event->bundle() !== 'course' || !$calendar_event->get('field_course')->entity) {
        continue;
      }

      if ($global_from === NULL) {
        $global_from = $calendar_event->get('from')->value;
      }
      if ($global_to === NULL) {
        $global_to = $calendar_event->get('to')->value;
      }

      if ($calendar_event->get('from')->value < $global_from) {
        $global_from = $calendar_event->get('from')->value;
      }
      if ($calendar_event->get('to')->value > $global_to) {
        $global_to = $calendar_event->get('to')->value;
      }

      $id = $calendar_event->id();

      $course_ids[] = $calendar_event->get('field_course')->target_id;

      $form['info'][$id] = [
        '#type' => 'fieldset',
        '#title' => $this->schemaSupportService->resolveCalenderEventName($calendar_event),
      ];

      $student_target_ids = $this->schemaSupportService->getStudentIds($calendar_event);

      $student_ids = array_merge($student_ids, $student_target_ids);
      $students = !empty($student_target_ids)
        ? $this->entityTypeManager->getStorage('user')->loadMultiple($student_target_ids)
        : [];

      $form['info'][$id]['students'] = [
        '#type' => 'details',
        '#title' => $this->t('Students (@count)', ['@count' => count($students)]),
        '#open' => FALSE,
      ];

      $student_names = [];
      /** @var \Drupal\user\UserInterface $student */
      foreach ($students as $student) {
        $student_names[] = $student->getDisplayName();
      }

      $form['info'][$id]['students']['list'] = [
        '#theme' => 'item_list',
        '#items' => $student_names,
      ];

      $form['calendar_events'][$id] = [
        '#type' => 'value',
        '#value' => $calendar_event->id(),
      ];
    }

    $form['course_ids'] = [
      '#type' => 'value',
      '#value' => array_unique($course_ids),
    ];

    $form['student_ids'] = [
      '#type' => 'value',
      '#value' => array_unique($student_ids),
    ];

    $form['global_from'] = [
      '#type' => 'value',
      '#value' => $global_from ?? 0,
    ];

    $form['global_to'] = [
      '#type' => 'value',
      '#value' => $global_to ?? 0,
    ];

    if (empty($form['info'])) {
      throw new NotFoundHttpException();
    }

    $form['conflict_behavior'] = [
      '#type' => 'select',
      '#title' => $this->t('Conflict behavior'),
      '#description' => $this->t('What to do if there is a conflict with the existing reported lessons. Could be due to a previous deviation report or if schema has recently been changed.'),
      '#options' => [
        'skip' => $this->t('Skip lesson (do nothing)'),
        'cancel' => $this->t('Cancel lesson'),
      ],
      '#default_value' => 'skip',
      '#required' => TRUE,
    ];

    $form['disclaimer'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('NOTE: Students that has registered absence during any part of the lesson will be set as valid absence from the lesson.'),
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

    // Initialize batch (to set title).
    $batch = [
      'title' => $this->t('Fast report lessons'),
      'init_message' => $this->t('Fast report lessons'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
      'finished' => [self::class, 'finished'],
    ];

    $to_cancel = [];
    $to_skip = [];
    $to_fast_report = [];

    $global_from = $form_state->getValue('global_from', 0);
    $global_to = $form_state->getValue('global_to', 0);
    $course_ids = $form_state->getValue('course_ids', []);
    $student_ids = $form_state->getValue('student_ids', []);

    $conflict_behavior = $form_state->getValue('conflict_behavior', 'skip');

    $calendar_event_ids = array_keys($form_state->getValue('calendar_events'));

    $calendar_events = !empty($calendar_event_ids) ? $this->entityTypeManager->getStorage('ssr_calendar_event')->loadMultiple($calendar_event_ids) : [];

    $previous_reports = [];

    if (!empty($calendar_events)) {
      // Get all previous reports.
      if (!empty($course_ids)) {
        $query = $this->connection->select('node__field_course', 'nfc');
        $query->innerJoin('node__field_course_sub_group', 'sb', 'nfc.entity_id = sb.entity_id');
        $query->innerJoin('node__field_class_start', 'cs', 'nfc.entity_id = cs.entity_id');
        $query->innerJoin('node__field_class_end', 'ce', 'nfc.entity_id = ce.entity_id');

        $results = $query->fields('nfc', ['entity_id', 'field_course_target_id'])
          ->fields('sb', ['field_course_sub_group_value'])
          ->fields('cs', ['field_class_start_value'])
          ->fields('ce', ['field_class_end_value'])
          ->condition('nfc.deleted', 0)
          ->condition('nfc.bundle', 'course_attendance_report')
          ->condition('nfc.field_course_target_id', $course_ids, 'IN')
          ->condition('cs.field_class_start_value', $global_to, '<')
          ->condition('ce.field_class_end_value', $global_from, '>')
          ->execute();

        foreach ($results as $result) {
          $course_id = $result->field_course_target_id;
          $sub_group_id = $result->field_course_sub_group_value ?? 'default';
          $report_from = $result->field_class_start_value;
          $report_to = $result->field_class_end_value;
          $previous_reports[$course_id][$sub_group_id][] = [
            'from' => $report_from,
            'to' => $report_to,
          ];
        }
      }
    }

    while (!empty($calendar_events)) {
      $calendar_event = array_pop($calendar_events);
      $has_report_conflict = FALSE;
      $has_event_conflict = FALSE;

      $event_from = $calendar_event->get('from')->value;
      $event_to = $calendar_event->get('to')->value;
      $event_course = $calendar_event->get('field_course')->target_id;
      $event_sub_group_id = $calendar_event->get('field_course_sub_group')->value ?? 'default';

      if (!empty($previous_reports[$event_course][$event_sub_group_id])) {
        foreach ($previous_reports[$event_course][$event_sub_group_id] as $previous_report) {
          if ($event_to <= $previous_report['from'] || $event_from >= $previous_report['to']) {
            continue;
          }
          $has_report_conflict = TRUE;
          if ($conflict_behavior === 'skip') {
            $to_skip[] = $calendar_event->id();
          }
          else {
            $to_cancel[] = $calendar_event->id();
          }
          break;
        }
      }

      if (!$has_report_conflict) {
        foreach ($calendar_events as $other_calendar_event) {
          if ($other_calendar_event->id() === $calendar_event->id()) {
            continue;
          }

          $other_event_course = $other_calendar_event->get('field_course')->target_id;
          $other_event_sub_group_id = $other_calendar_event->get('field_course_sub_group')->value ?? 'default';

          if ($other_event_course !== $event_course || $other_event_sub_group_id !== $event_sub_group_id) {
            continue;
          }

          $other_event_from = $other_calendar_event->get('from')->value;
          $other_event_to = $other_calendar_event->get('to')->value;

          if ($event_to <= $other_event_from || $event_from >= $other_event_to) {
            continue;
          }

          $has_event_conflict = TRUE;
          if ($conflict_behavior === 'skip') {
            $to_skip[] = $calendar_event->id();
          }
          else {
            $to_cancel[] = $calendar_event->id();
          }
          break;
        }
      }

      if (!$has_report_conflict && !$has_event_conflict) {
        $to_fast_report[] = $calendar_event->id();
      }
    }

    foreach ($to_cancel as $calendar_event_id) {
      $batch['operations'][] = [[self::class, 'batchCancel'], [$calendar_event_id]];
    }

    foreach ($to_skip as $calendar_event_id) {
      $batch['operations'][] = [[self::class, 'batchSkip'], [$calendar_event_id]];
    }

    $student_absence_map = [];
    if (!empty($to_fast_report) && !empty($student_ids)) {
      // Resolve absence map.
      $absence_nids = AbsenceDayHandler::getAbsenceNodesFromPeriod($student_ids, $global_from, $global_to, TRUE);

      if (!empty($absence_nids)) {
        $query = $this->connection->select('node__field_absence_from', 'af');
        $query->innerJoin('node__field_absence_to', 'at', 'af.entity_id = at.entity_id');
        $query->innerJoin('node__field_student', 's', 'af.entity_id = s.entity_id');
        $query->condition('af.entity_id', $absence_nids, 'IN');

        $results = $query->fields('af', ['field_absence_from_value'])
          ->fields('at', ['field_absence_to_value'])
          ->fields('s', ['field_student_target_id'])
          ->execute();

        foreach ($results as $result) {
          $student_id = $result->field_student_target_id;
          $from = $result->field_absence_from_value;
          $to = $result->field_absence_to_value;
          $student_absence_map[$student_id][] = [
            'from' => $from,
            'to' => $to,
          ];
        }
      }
    }

    foreach ($to_fast_report as $calendar_event_id) {
      $batch['operations'][] = [[self::class, 'batchFastReport'], [$calendar_event_id, $student_absence_map]];
    }

    if (!empty($batch['operations'])) {
      if (count($batch['operations']) < 5) {
        $batch['progressive'] = FALSE;
      }
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('No lessons to fast report'));
    }
  }

  public static function batchCancel($calendar_event_id, &$context): void {
    $calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($calendar_event_id);
    if (!$calendar_event || $calendar_event->get('completed')->value) {
      return;
    }

    $calendar_event->set('status', TRUE);
    $calendar_event->set('completed', FALSE);
    $calendar_event->set('cancelled', TRUE);
    $calendar_event->save();
    $context['results']['handled_events'][$calendar_event_id] = TRUE;
    $context['results']['cancelled_events'][$calendar_event_id] = TRUE;
  }

  public static function batchSkip(string|int $calendar_event_id, &$context): void {
    $context['results']['handled_events'][$calendar_event_id] = TRUE;
    $context['results']['skipped_events'][$calendar_event_id] = TRUE;
  }

  public static function batchFastReport(string|int $calendar_event_id, array $student_absence_map, &$context): void {
    try {
      /** @var \Drupal\simple_school_reports_entities\CalendarEventInterface $calendar_event */
      $calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($calendar_event_id);
      if (!$calendar_event || $calendar_event->get('completed')->value) {
        return;
      }

      $course = $calendar_event->get('field_course')->entity;
      if (!$course) {
        return;
      }

      $title = $course->label();
      $from_date = new \DateTime();
      $from_date->setTimestamp($calendar_event->get('from')->value);
      $duration = abs(floor(($calendar_event->get('to')->value - $calendar_event->get('from')->value) / 60));
      $title .= ' ' . $from_date->format('Y-m-d H:i') . ' (' . $duration . ' min)';

      $node = \Drupal::entityTypeManager()->getStorage('node')->create([
        'type' => 'course_attendance_report',
        'title' => $title,
        'field_course' => ['target_id' => $calendar_event->get('field_course')->target_id],
        'field_course_sub_group' => ['target_id' => $calendar_event->get('field_course_sub_group')->value ?? 'default'],
        'field_class_start' => $calendar_event->get('from')->value,
        'field_class_end' => $calendar_event->get('to')->value,
        'field_duration' => $duration,
        'langcode' => 'sv',
      ]);

      $paragraphs = [];

      /** @var \Drupal\simple_school_reports_schema_support\Service\SchemaSupportServiceInterface $schema_support_service */
      $schema_support_service = \Drupal::service('simple_school_reports_schema_support.schema_support');
      $student_target_ids = $schema_support_service->getStudentIds($calendar_event);

      $paragraph_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

      // Skip students in a CBT/BT (bonustimme) course.
      $course_short_name = SchoolSubjectHelper::getSubjectShortName($course->get('field_school_subject')->target_id);
      if ($course_short_name === 'CBT' || $course_short_name === 'BT') {
        $student_target_ids = [];
      }

      foreach ($student_target_ids as $student_target_id) {
        /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
        $paragraph = $paragraph_storage->create([
          'type' => 'student_course_attendance',
          'langcode' => 'sv',
        ]);

        $attendance_type = 'attending';

        if (!empty($student_absence_map[$student_target_id])) {
          $student_absences = $student_absence_map[$student_target_id];
          foreach ($student_absences as $absence) {
            if ($absence['to'] <= $calendar_event->get('from')->value || $absence['from'] >= $calendar_event->get('to')->value) {
              continue;
            }
            $attendance_type = 'valid_absence';
            break;
          }
        }

        $paragraph->set('field_attendance_type', $attendance_type);
        $paragraph->set('field_invalid_absence', 0);
        $paragraph->set('field_invalid_absence_original', 0);
        $paragraph->set('field_student', ['target_id' => $student_target_id]);
        $paragraph->set('field_subject', ['target_id' => $course->get('field_school_subject')->target_id]);
        $paragraph->setNewRevision(FALSE);
        $paragraphs[] = $paragraph;
      }

      $node->set('field_student_course_attendance', $paragraphs);
      $node->save();

      $calendar_event->set('status', TRUE);
      $calendar_event->set('completed', TRUE);
      $calendar_event->set('cancelled', FALSE);
      $calendar_event->save();

      $context['results']['handled_events'][$calendar_event_id] = TRUE;
      $context['results']['reported_events'][$calendar_event_id] = TRUE;
    }
    catch (\Exception $e) {
      return;
    }
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['handled_events'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    if (!empty($results['reported_events'])) {
      \Drupal::messenger()->addStatus(t('@count lessons has been reported.', ['@count'  => count($results['reported_events'])]));
    }
    if (!empty($results['skipped_events'])) {
      \Drupal::messenger()->addStatus(t('@count lessons has been skipped.', ['@count'  => count($results['skipped_events'])]));
    }
    if (!empty($results['cancelled_events'])) {
      \Drupal::messenger()->addStatus(t('@count lessons has been cancelled.', ['@count'  => count($results['cancelled_events'])]));
    }

  }
}
