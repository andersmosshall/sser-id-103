<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a confirmation form for adding date to url.
 */
class DateToUrlForm extends ConfirmFormBase {

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
    RequestStack $request_stack,
    RouteMatchInterface $route_match
  ) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->currentRouteMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'date_to_url_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Date');
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

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, bool $skip_cancel = FALSE) {
    $date_string = $this->currentRequest->query->get('date');
    try {
      $date = new \DateTime($date_string);
    } catch (\Exception $e) {
      $date = NULL;
    }
    if (!$date) {
      $now = (new \DateTime())->format('Y-m-d');
      return $this->redirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => ['date' => $now]]);
    }

    $form['date'] = [
      '#type' => 'date',
      '#default_value' => $date,
      '#required' => TRUE,
    ];

    $form = parent::buildForm($form, $form_state);
    unset($form['#title']);

    if ($skip_cancel) {
      unset($form['actions']['cancel']);
    }

    return $form;
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
      $date = $form_state->getValue('date');
      if (!$date) {
        $this->messenger()->addError($this->t('Something went wrong. Try again.'));
        return;
      }
      $form_state->setRedirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => ['date' => $date]]);
    }
  }
}
