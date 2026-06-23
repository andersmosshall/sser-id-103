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
class AfterSchoolChildCareDayCheckInOverviewFilterForm extends ConfirmFormBase {

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
    return 'after_school_child_care_day_check_in_overview_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Filter');
  }

  public function getCancelRoute() {
    return 'simple_school_reports_core_fth.overview_day';
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
    $groups = $this->currentRequest->query->get('groups');
    if (is_string($groups) && $groups !== '') {
      $groups = explode(',', $groups);
    }

    try {
      $date = $date_string ? new \DateTime($date_string) : NULL;
    } catch (\Exception $e) {
      $date = NULL;
    }

    $form['fields'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['child-care-filter-fields']
      ]
    ];

    $form['fields']['date'] = [
      '#type' => 'date',
      '#default_value' => $date?->format('Y-m-d'),
      '#required' => TRUE,
    ];

    $form['fields']['all_groups'] = [
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

    if ($groups != $default_values) {
      $query_group_defaults = $default_values;
      $groups = NULL;
    }

    if (!$date || !is_array($groups)) {
      $now = (new \DateTime())->format('Y-m-d');

      $query = [
        'date' => $date?->format('Y-m-d') ?? $now,
        'groups' => implode(',', $query_group_defaults),
      ];
      if ($this->currentRequest->query->get('destination')) {
        $query['destination'] = $this->currentRequest->query->get('destination');
      }

      return $this->redirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => $query]);
    }

    if ($has_all) {
      $form['all_groups']['#default_value'] = TRUE;
    }

    $form['fields']['groups_wrapper'] = [
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
    $form['fields']['groups_wrapper']['groups'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $default_values,
    ];



    $default_filter = $this->currentRequest->query->get('filter', 'all');
    $filter_options = [
      'all' => $this->t('All'),
      'not_checked_in' => $this->t('Not checked in'),
      'checked_in' => $this->t('Checked in'),
      'checked_out' => $this->t('Checked out'),
    ];
    $form['fields']['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter'),
      '#options' => $filter_options,
      '#default_value' => $default_filter,
    ];

    $default_include_unscheduled = !!$this->currentRequest->query->get('include_unscheduled', FALSE);
    $form['fields']['include_unscheduled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include unscheduled students'),
      '#default_value' => $default_include_unscheduled,
      // Only visible if filter is all or not_checked_in.
      '#states' => [
        'visible' => [
          ':input[name="filter"]' => [
            ['value' => 'all'],
            ['value' => 'not_checked_in'],
          ],
        ]
      ]
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
      $date = $form_state->getValue('date');
      if (!$date) {
        $this->messenger()->addError($this->t('Something went wrong. Try again.'));
        return;
      }

      $query = [
        'date' => $date,
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
      $query['filter'] = $form_state->getValue('filter');

      if (($query['filter'] === 'all' || $query['filter'] === 'not_checked_in') && $form_state->getValue('include_unscheduled')) {
        $query['include_unscheduled'] = '1';
      }


      $this->currentRequest->query->remove('destination');
      $form_state->setRedirect($this->currentRouteMatch->getRouteName(), $this->currentRouteMatch->getRawParameters()->all(), ['query' => $query]);
    }
  }
}
