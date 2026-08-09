<?php

namespace Drupal\simple_school_reports_core_fth\Form;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a confirmation form for adding date to url.
 */
class AfterSchoolChildCareMonthOverviewFilterForm extends ConfirmFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    RouteMatchInterface $route_match
  ) {
    $this->entityTypeManager = $entity_type_manager;
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
    return 'after_school_child_care_month_overview_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Filter');
  }

  public function getCancelRoute() {
    return 'simple_school_reports_core_fth.overview_month';
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
    $from = $this->currentRequest->query->get('from');
    $to = $this->currentRequest->query->get('to');
    $groups = $this->currentRequest->query->get('groups');
    if (is_string($groups) && $groups !== '') {
      $groups = explode(',', $groups);
    }

    $from_date = NULL;
    $to_date = NULL;

    try {
      if (is_numeric($from)) {
        $from_date = new \DateTime();
        $from_date->setTimestamp($from);
      }
      if (is_numeric($to)) {
        $to_date = new \DateTime();
        $to_date->setTimestamp($to);
      }
    }
    catch (\Exception $e) {
      $from_date = NULL;
      $to_date = NULL;
    }

    $form['from_date'] = [
      '#type' => 'date',
      '#title' => $this->t('From'),
      '#default_value' => $from_date?->format('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['to_date'] = [
      '#type' => 'date',
      '#title' => $this->t('To'),
      '#default_value' => $to_date?->format('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['all_groups'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All groups'),
    ];

    $options = [];
    /** @var \Drupal\simple_school_reports_child_care_support\ChildCareInterface[] $child_care_entities */
    $child_care_entities = $this->entityTypeManager->getStorage('ssr_child_care')->loadByProperties(['status' => TRUE, 'bundle' => 'fth']);
    $default_values = [];
    $query_group_defaults = [];
    $has_all = TRUE;
    foreach ($child_care_entities as $child_care) {
      $options[$child_care->id()] = $child_care->label() . ' (' . $child_care->getShortLabel() . ')';
      if ($child_care->access('check_in_out')) {
        $query_group_defaults[] = $child_care->id();
      }
      if (is_array($groups) && in_array($child_care->id(), $groups)) {
        $default_values[] = $child_care->id();
      }
      else {
        $has_all = FALSE;
      }
    }
    asort($options);
    if (is_array($groups)) {
      sort($groups);
    }
    sort($default_values);

    if ($groups != $default_values && !empty($default_values)) {
      $query_group_defaults = $default_values;
      $groups = NULL;
    }

    if (!$from_date || !$to_date || !is_array($groups)) {
      if (!$from_date || !$to_date) {
        $now = new \DateTime();
        $first_day = $now->format('Y-m-01');
        $last_day = $now->format('Y-m-t');
        $from_date = new \DateTime($first_day. ' 00:00:00');
        $to_date = new \DateTime($last_day . ' 23:59:59');
      }

      $query = [
        'from' => $from_date->getTimestamp(),
        'to' => $to_date->getTimestamp(),
        'groups' => implode(',', $query_group_defaults) ?: '-1',
      ];
      if ($this->currentRequest->query->get('destination')) {
        $query['destination'] = $this->currentRequest->query->get('destination');
      }

      return $this->redirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => $query]);
    }

    if ($has_all) {
      $form['all_groups']['#default_value'] = TRUE;
    }

    $form['groups_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['child-care-overview-filter-groups-wrapper']
      ],
      '#states' => [
        'visible' => [
          ':input[name="all_groups"]' => ['checked' => FALSE],
        ]
      ]
    ];
    $form['groups_wrapper']['groups'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $default_values,
    ];

    $form = parent::buildForm($form, $form_state);
    unset($form['#title']);

    if ($skip_cancel) {
      unset($form['actions']['cancel']);
    }

    $cache = new CacheableMetadata();
    $cache->addCacheContexts(['user']);
    $cache->addCacheTags(['ssr_child_care_list']);
    $cache->applyTo($form);
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
      $from_date_string = $form_state->getValue('from_date');
      $to_date_string = $form_state->getValue('to_date');
      if (!$from_date_string || !$to_date_string) {
        $this->messenger()->addError($this->t('Something went wrong. Try again.'));
        return;
      }
      $from_date = new \DateTime($from_date_string . ' 00:00:00');
      $to_date = new \DateTime($to_date_string . ' 23:59:59');

      $query = [
        'from' => $from_date->getTimestamp(),
        'to' => $to_date->getTimestamp(),
      ];
      $groups = $form_state->getValue('groups');

      if (!$form_state->getValue('all_groups')) {
        $query['groups'] = [];
        foreach ($groups as $id => $group) {
          if ($group) {
            $query['groups'][] = $id;
          }
        }
        if (empty($query['groups'])) {
          $query['groups'][] = '-1';
        }
      }
      else {
        $query['groups'] = array_keys($groups);
      }
      $query['groups'] = implode(',', $query['groups']);

      $this->currentRequest->query->remove('destination');
      $form_state->setRedirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => $query]);
    }
  }
}
