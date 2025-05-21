<?php

namespace Drupal\simple_school_reports_module_info\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_module_info\Service\ModuleInfoServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for bug report.
 */
class SyncModuleInfoForm extends ConfirmFormBase {


  public function __construct(
    protected ModuleInfoServiceInterface $moduleInfoService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_module_info.module_info_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sync_module_info_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Sync module info');
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
    return $this->t('Synchronize');
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['hide_price_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide price info.'),
      '#default_value' => \Drupal::state()
        ->get('ssr_module_info.hide_price_info', FALSE),
    ];
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

    $hide_price_info = !!$form_state->getValue('hide_price_info');
    \Drupal::state()->set('ssr_module_info.hide_price_info', $hide_price_info);

    $this->moduleInfoService->syncModuleInfo(TRUE);
    $this->messenger()->addStatus($this->t('Module info item created.'));
    Cache::invalidateTags(['ssr_module_info.settings']);
  }

}
