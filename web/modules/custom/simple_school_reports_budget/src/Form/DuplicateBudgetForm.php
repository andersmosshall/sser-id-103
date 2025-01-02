<?php

namespace Drupal\simple_school_reports_budget\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\NodeCloneServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for duplicate list template.
 */
class DuplicateBudgetForm extends ConfirmFormBase {


  /**
   * @var \Drupal\simple_school_reports_core\Service\NodeCloneServiceInterface
   */
  protected $cloneService;

  /**
   * @var NodeInterface
   */
  protected $budget;

  public function __construct(NodeCloneServiceInterface $clone_service) {
    $this->cloneService = $clone_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.node_clone'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clone_budget_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $name = $this->budget ? $this->budget->label() : '';

    return $this->t('Duplicate budget @name', ['@name' => $name]);
  }

  public function getCancelRoute() {
    return 'view.list_template.list';
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
    return $this->t('Duplicate', [], ['context' => 'copy']);
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
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'budget') {
      throw new AccessDeniedHttpException();
    }

    $this->budget = $node;

    $form['budget'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of new budget'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form = parent::buildForm($form, $form_state);

    $form['#title'] = $this->t('Duplicate budget @name', ['@name' => $node->label()]);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $original = $this->budget;

      if (!$original) {
        throw new \RuntimeException();
      }

      $label = $form_state->getValue('label', 'Untitled');

      $fields = [];
      $reference_fields = [
        'field_budget_row' => [
          'fields' => [
            'field_age_limit_from',
            'field_age_limit_to',
            'field_mean_salary',
            'field_annual_worker',
            'field_label',
            'field_row_type',
            'field_expense',
            'field_expense',
          ],
        ],
      ];


      $new_node = $this->cloneService->clone($original, $label, $fields, $reference_fields);

      $this->messenger()->addStatus($this->t('@type %title has been created.', ['@type' => $this->t('Budget'), '%title' => $label]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Something went wrong'));
    }
  }
}
