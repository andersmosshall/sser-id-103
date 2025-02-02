<?php

namespace Drupal\simple_school_reports_schema_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_entities\CalendarEventInterface;
use Drupal\simple_school_reports_schema_support\Service\SchemaSupportServiceInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for undo of canceling course event.
 */
class UndoCancelCourseEventForm extends ConfirmFormBase {

  protected NodeInterface | null $course = NULL;

  protected CalendarEventInterface | null $calendarEvent = NULL;

  /**
   * Constructs a new ResetInvalidAbsenceMultipleForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SchemaSupportServiceInterface $schemaSupportService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_schema_support.schema_support'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'undo_cancel_course_event_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $t_args = [
      '@label' => $this->t('this lesson'),
    ];

    if ($this->calendarEvent) {
      $t_args['@label'] = $this->schemaSupportService->resolveCalenderEventName($this->calendarEvent);
    }

    return $this->t('Are you sure you want to undo cancel @label?', $t_args);
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
    return $this->t('Undo cancel lesson');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getSuccessMessage() {
    $t_args = [
      '@label' => $this->t('Lesson'),
    ];
    if ($this->calendarEvent) {
      $t_args['@label'] = $this->schemaSupportService->resolveCalenderEventName($this->calendarEvent);
    }
    return $this->t('@label cancelled', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL, ?CalendarEventInterface $ssr_calendar_event = NULL) {
    if (!$node || $node->bundle() !== 'course' || !$ssr_calendar_event || $ssr_calendar_event->bundle() !== 'course') {
      throw new AccessDeniedHttpException();
    }

    $course = $ssr_calendar_event->get('field_course')->target_id;
    if ($course !== $node->id()) {
      throw new NotFoundHttpException();
    }

    $this->course = $node;
    $this->calendarEvent = $ssr_calendar_event;

    $form['calendar_event_id'] = [
      '#type' => 'value',
      '#value' => $ssr_calendar_event->id(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $calendar_event_id = $form_state->getValue('calendar_event_id');
    $calendar_event = $calendar_event_id ? $this->entityTypeManager->getStorage('ssr_calendar_event')->load($calendar_event_id) : NULL;
    if ($calendar_event && !$calendar_event->get('completed')->value) {
      $calendar_event->set('status', TRUE);
      $calendar_event->set('completed', FALSE);
      $calendar_event->set('cancelled', FALSE);
      $calendar_event->save();
      $this->messenger()->addStatus($this->getSuccessMessage());
    }
    else {
      $this->messenger()->addError('Something went wrong. Try again.');
    }
  }

  public static function access(AccountInterface $account, ?NodeInterface $node = NULL, ?CalendarEventInterface $ssr_calendar_event = NULL) {
    if (!ssr_use_schema()) {
      return AccessResult::forbidden();
    }

    if (!$node || $node->bundle() !== 'course' || !$ssr_calendar_event || $ssr_calendar_event->bundle() !== 'course') {
      return AccessResult::forbidden();
    }

    if ($node->id() !== $ssr_calendar_event->get('field_course')->target_id) {
      return AccessResult::forbidden()->addCacheableDependency($node)->addCacheableDependency($ssr_calendar_event);
    }

    $access = AccessResult::allowedIf($node->access('update', $account, FALSE) && !$ssr_calendar_event->get('completed')->value);
    $access->addCacheContexts(['user']);
    $access->addCacheableDependency($node);
    $access->addCacheableDependency($ssr_calendar_event);

    return $access;
  }

}
