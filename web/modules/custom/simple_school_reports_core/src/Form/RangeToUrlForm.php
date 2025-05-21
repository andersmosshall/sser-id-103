<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for adding date range to url.
 */
class RangeToUrlForm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\simple_school_reports_core\Service\TermServiceInterface
   */
  protected $termService;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;



  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    EntityTypeManagerInterface $entity_type_manager,
    TermServiceInterface $term_service,
    RequestStack $request_stack,
    RouteMatchInterface $route_match
  ) {
    $this->tempStoreFactory = $temp_store_factory->get('range_selector_to_url');
    $this->entityTypeManager = $entity_type_manager;
    $this->termService = $term_service;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('simple_school_reports_core.term_service'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'range_to_url_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Range selector');
  }

  public function getCancelRoute() {
    return '<front>';
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
    return $this->t('Apply');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  protected function autoSubmitRoutes(): array {
    return [
      'simple_school_reports_core.student_statistics',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    if (in_array($this->getRouteMatch()->getRouteName(), $this->autoSubmitRoutes())) {
      $form['#attached']['library'][] = 'simple_school_reports_core/range_to_url_autosubmit';
      $form['#attributes']['class'][] = 'range-to-url-form';
    }


    $from = $this->currentRequest->get('from');
    $to = $this->currentRequest->get('to');

    if (!$from || !$to) {
      $from = $this->termService->getCurrentTermStart(FALSE);
      $to = $this->termService->getCurrentTermEnd(FALSE);

      if ($from && $to) {
        return $this->redirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => ['from' => $from, 'to' => $to]]);
      }
    }
    $from_date = new DrupalDateTime();
    if ($from) {
      $from_date->setTimestamp($from);
    }
    $from_date->setTime(0,0);

    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('From'),
      '#default_value' => $from_date->format('Y-m-d'),
      '#required' => TRUE,
    ];

    $to_date = new DrupalDateTime();
    if ($to) {
      $to_date->setTimestamp($to);
    }
    $to_date->setTime(23,59);

    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('To'),
      '#default_value' => $to_date->format('Y-m-d'),
      '#required' => TRUE,
    ];

    $form = parent::buildForm($form, $form_state);

    unset($form['#title']);

    return $form;
  }

  protected function getTimestamp(string $date_string, string $fallback_time = '00:00:00'): int {
    return (new \DateTime($date_string . ' ' . $fallback_time))->getTimestamp();
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $from = $this->getTimestamp($form_state->getValue('from_date'));
    $to = $this->getTimestamp($form_state->getValue('to_date'), '23:59:59');

    if ($from <= 0) {
      $form_state->setErrorByName('from_date', $this->t('@name field is required.', ['@name' => $this->t('Absence from')]));
      return;
    }

    if ($to <= 0) {
      $form_state->setErrorByName('to_date', $this->t('@name field is required.', ['@name' => $this->t('Absence to')]));
      return;
    }

    if ($to < $from) {
      $form_state->setErrorByName('to_date', $this->t('%name must be higher than or equal to %min.', ['%name' => $this->t('Absence to'), '%min' => $this->t('Absence from')]));
    }
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

    if ($form_state->getValue('confirm')) {

      $from = $this->getTimestamp($form_state->getValue('from_date'));
      $to = $this->getTimestamp($form_state->getValue('to_date'), '23:59:59');

      $form_state->setRedirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => ['from' => $from, 'to' => $to]]);
    }
  }
}
