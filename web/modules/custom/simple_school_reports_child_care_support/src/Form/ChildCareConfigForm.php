<?php

namespace Drupal\simple_school_reports_child_care_support\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\simple_school_reports_child_care_support\Service\ChildCareServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for student leave applications.
 */
class ChildCareConfigForm extends FormBase {

  public function __construct(
    protected ChildCareServiceInterface $childCareService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_child_care_support.child_care'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'child_care_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->childCareService->getSettings();

    $form['future_min_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum days ahead'),
      '#description' => $this->t('Minimum number of days ahead a new child care schema entry can be created or updated.'),
      '#default_value' => $settings['future_min_limit'],
      '#min' => 0,
      '#max' => 365,
    ];

    $form['future_max_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum days ahead'),
      '#description' => $this->t('Maximum number of days ahead a new child care schema entry can be created or updated.'),
      '#default_value' => $settings['future_max_limit'],
      '#min' => 0,
      '#max' => 365,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = [
      'future_min_limit' => $form_state->getValue('future_min_limit'),
      'future_max_limit' => $form_state->getValue('future_max_limit'),
    ];
    $this->childCareService->setSettings($settings);
    Cache::invalidateTags(['ssr_child_care_list']);
    $this->messenger()->addStatus($this->t('Configuration has been saved'));
  }

  public function access(AccountInterface $account): AccessResult {
    if (!ssr_use_child_care()) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowedIfHasPermission($account, 'administer simple school reports settings');
  }

}
