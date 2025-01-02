<?php

namespace Drupal\simple_school_reports_budget\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining BudgetService.
 */
interface BudgetServiceInterface {

  /**
   * @param bool $labels_only
   *
   * @return array
   */
  public function getRowTypeDefinitions(bool $labels_only = FALSE): array;

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $formState
   */
  public function budgetRowFormAlter(&$form, FormStateInterface $formState, $delta);


  public function buildBudgetTable(NodeInterface $budget, array &$form, ?FormStateInterface $form_state = NULL);

}
