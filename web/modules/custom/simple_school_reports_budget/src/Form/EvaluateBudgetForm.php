<?php

namespace Drupal\simple_school_reports_budget\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_budget\Service\BudgetServiceInterface;
use Drupal\simple_school_reports_core\Service\NodeCloneServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for evaluating budget.
 */
class EvaluateBudgetForm extends FormBase {


  /**
   * @var  \Drupal\simple_school_reports_budget\Service\BudgetServiceInterface
   */
  protected $budgetService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var NodeInterface
   */
  protected $budget;

  public function __construct(BudgetServiceInterface $budget_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->budgetService = $budget_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_budget.budget_service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'evaluate_budget_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'budget') {
      throw new AccessDeniedHttpException();
    }

    $this->budget = $node;

    $this->budgetService->buildBudgetTable($node, $form, $form_state);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $real_sum_values = $form_state->getValue('table', []);
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');


    foreach ($real_sum_values as $pid => $data) {
      $value = $data['real_sum'] ?? 0;
      $paragraph = $paragraph_storage->load($pid);
      if ($paragraph) {
        $paragraph->set('field_real_value', $value);
        $paragraph->save();
      }
    }
    $this->messenger()->addStatus('Evaluate values updated');
  }
}
