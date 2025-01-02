<?php

namespace Drupal\simple_school_reports_post_check\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for set callstack form.
 */
class SetCallstackForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssr_set_callstack_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Set callstack form');
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
    return $this->t('Send');
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

    if (!$this->currentUser()->hasPermission('administer modules')) {
      throw new AccessDeniedHttpException();
    }

    $default_value = \Drupal\simple_school_reports_post_check\CallstackHelper::ssrCallstackEnabled();
    $form['callstack_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $default_value,
    ];

    return parent::buildForm($form, $form_state);
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
      $callstack_enabled = $form_state->getValue('callstack_enabled', FALSE);
      \Drupal\simple_school_reports_post_check\CallstackHelper::ssrSetCallstackEnabled($callstack_enabled);
      $this->messenger()->addStatus($this->t('Setting updated.'));
    }
  }

}
