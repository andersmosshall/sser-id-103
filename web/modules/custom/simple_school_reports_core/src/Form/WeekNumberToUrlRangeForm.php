<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\Service\TermServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a confirmation form for adding date range to url.
 */
class WeekNumberToUrlRangeForm extends ConfirmFormBase {

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
    return 'week_number_to_url_range';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Week number');
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

  public static function getFirstDayOfWeek(DrupalDateTime $date) : DrupalDateTime {
    $return = clone $date;
    $return->setTime(0,0,0);
    $day_number = $return->format('N');

    $day_diff = $day_number - 1;

    if ($day_diff >= 1) {
      $return->sub(new \DateInterval('P' . $day_diff . 'D'));
    }
    return $return;
  }

  public static function getLastDayOfWeek(DrupalDateTime $date) : DrupalDateTime {
    $return = self::getFirstDayOfWeek($date);
    $return->add(new \DateInterval('P6D'));
    $return->setTime(23,59,59);
    return $return;
  }

  public static function getWeekOptions(?string $current_week_value = NULL, null | DrupalDateTime | \DateTime $max_from = NULL, null | DrupalDateTime | \DateTime $max_to = null): array {
    $current_week_key = NULL;
    if ($current_week_value) {
      // Set current week as value -1.
      $default_date = new DrupalDateTime();
      $default_date = WeekNumberToUrlRangeForm::getFirstDayOfWeek($default_date);
      $current_week_key = 'mts:' . $default_date->getTimestamp();
    }

    $date_walk = self::getFirstDayOfWeek(new DrupalDateTime());
    $date_walk->sub(new \DateInterval('P1092D'));
    $week_options = [];
    for ($i = 0; $i < 166; $i++) {
      $key = 'mts:' . $date_walk->getTimestamp();
      if ($current_week_value && $current_week_key === $key) {
        $key = $current_week_value;
      }

      if ($max_from && $date_walk->getTimestamp() < $max_from->getTimestamp()) {
        $date_walk->add(new \DateInterval('P7D'));
        continue;
      }
      if ($max_to && $date_walk->getTimestamp() > $max_to->getTimestamp()) {
        break;
      }

      $week_options[$key] = $date_walk->format('Y - \v. W');
      $date_walk->add(new \DateInterval('P7D'));
    }

    return $week_options;
  }

  public static function getTimestampFromOptionValue(string $value): int {
    $value = str_replace('mts:', '', $value);
    return (int) $value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $from = $this->currentRequest->get('from');
    $to = $this->currentRequest->get('to');

    $default_date = new DrupalDateTime();
    if ($from) {
      $default_date->setTimestamp($from);
    }
    $default_date = $this->getFirstDayOfWeek($default_date);
    $default_value = $default_date->getTimestamp();

    if (!$from || !$to) {
      $from = $default_value;
      $to = $this->getLastDayOfWeek($default_date)->getTimestamp();
      if ($from && $to) {
        return $this->redirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => ['from' => $from, 'to' => $to]]);
      }
    }

    $options = [];
    if ($this->currentRouteMatch->getRouteName() === 'simple_school_reports_schema.student_schema') {
      $max_from = new \DateTime();
      $max_from->setTime(0, 0, 0);
      // Subtract 3 weeks.
      $max_from->sub(new \DateInterval('P21D'));
      $options = $this->getWeekOptions(NULL, $max_from, $this->termService->getDefaultSchoolYearEnd());
    }
    else {
      $this->getWeekOptions();
    }

    $form['from_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Select week'),
      '#default_value' => 'mts:' . $default_value,
      '#required' => TRUE,
      '#options' => $options,
    ];

    $form = parent::buildForm($form, $form_state);
    unset($form['#title']);
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
      /** @var DrupalDateTime $from_date */
      $from_date = (new DrupalDateTime())->setTimestamp($this->getTimestampFromOptionValue($form_state->getValue('from_date')));
      $from_date = $this->getFirstDayOfWeek($from_date);
      $to_date = $this->getLastDayOfWeek($from_date);

      if (!$from_date || !$to_date) {
        $this->messenger()->addError($this->t('Something went wrong. Try again.'));
        return;
      }
      $form_state->setRedirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => ['from' => $from_date->getTimestamp(), 'to' => $to_date->getTimestamp()]]);
    }
  }
}
