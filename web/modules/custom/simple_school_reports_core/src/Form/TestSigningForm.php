<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_entities\SsrSigningInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form test of signing.
 */
class TestSigningForm extends ConfirmWithSigningFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_sign_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Test signing');
  }

  public function getCancelRoute() {
    return 'view.students.students';
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
  protected function beforeSigningBuildForm($form, $form_state): array {
    $form['test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test field'),
      '#default_value' => 'foo bar',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function makeSignTemplate(array $safe_form_values, FormStateInterface $form_state): string {
    $val = $safe_form_values['test'] ?? 'error';
    return '<p>This is a test sign template:<br><br>Value: ' . $val . '</p>';
  }

  /**
   * {@inheritdoc}
   */
  protected function afterSigningSubmit(array &$form, FormStateInterface $form_state, array $safe_form_values, SsrSigningInterface $signing) {
    $val = $safe_form_values['test'] ?? 'error';
    $this->messenger()->addStatus($val);
  }

}
