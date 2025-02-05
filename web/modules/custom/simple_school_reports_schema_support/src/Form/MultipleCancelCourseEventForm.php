<?php

namespace Drupal\simple_school_reports_schema_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
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
 * Provides a confirmation form for canceling course event.
 */
class MultipleCancelCourseEventForm extends ConfirmFormBase {

  /**
   * Constructs a new MailMultipleCaregiversForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected SchemaSupportServiceInterface $schemaSupportService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('simple_school_reports_schema_support.schema_support'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multiple_cancel_course_event_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to cancel the lessons?');
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
    return $this->t('Cancel lessons');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * @param \Drupal\taxonomy\TermInterface $school_subject
   */
  public function setStatus(TermInterface $school_subject) {
    $school_subject->set('status', 1);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the accounts to be canceled from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $calendar_events = $this->tempStoreFactory
      ->get('cancel_multiple_course_events')
      ->get($this->currentUser()->id());
    if (empty($calendar_events)) {
      return $this->redirect($this->getCancelRoute());
    }

    $names = [];

    $form['calendar_events'] = ['#tree' => TRUE];
    /** @var CalendarEventInterface $calendar_event */
    foreach ($calendar_events as $calendar_event) {
      if ($calendar_event->bundle() !== 'course' || !$calendar_event->get('field_course')->entity) {
        continue;
      }
      $id = $calendar_event->id();

      $names[$id] = $this->schemaSupportService->resolveCalenderEventName($calendar_event);
      $form['calendar_events'][$id] = [
        '#type' => 'value',
        '#value' => $calendar_event->id(),
      ];
    }

    $form['calendar_events']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
    ];

    if (empty($names)) {
      throw new AccessDeniedHttpException();
    }

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
      'title' => $this->t('Cancel lessons'),
      'init_message' => $this->t('Cancel lessons'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'operations' => [],
      'finished' => [self::class, 'finished'],
    ];

    foreach ($form_state->getValue('calendar_events') as $calendar_event_id) {
      $batch['operations'][] = [[self::class, 'batchCancelEvent'], [$calendar_event_id]];
    }

    if (!empty($batch['operations'])) {
      if (count($batch['operations']) < 10) {
        $batch['progressive'] = FALSE;
      }
      batch_set($batch);
    }
    else {
      $this->messenger()->addWarning($this->t('No lessons has been cancelled.'));
    }
  }

  public static function batchCancelEvent($calendar_event_id, &$context) {
    $calendar_event = \Drupal::entityTypeManager()->getStorage('ssr_calendar_event')->load($calendar_event_id);
    if (!$calendar_event || $calendar_event->get('completed')->value) {
      return;
    }

    $calendar_event->set('status', TRUE);
    $calendar_event->set('completed', FALSE);
    $calendar_event->set('cancelled', TRUE);
    $calendar_event->save();
    $context['results']['cancelled_events'][$calendar_event_id] = TRUE;
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['cancelled_events'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    \Drupal::messenger()->addStatus(t('@count lessons has been cancelled.', ['@count'  => count($results['cancelled_events'])]));
  }
}
