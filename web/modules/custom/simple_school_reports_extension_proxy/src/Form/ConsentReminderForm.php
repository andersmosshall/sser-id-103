<?php

namespace Drupal\simple_school_reports_extension_proxy\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Form\MailMultipleUsersForm;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for ConsentReminderForm accounts.
 */
class ConsentReminderForm extends MailMultipleUsersForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_consent_reminders';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Mail users');
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
    $form = parent::buildForm($form, $form_state);
    $message_template = $this->messageTemplateService->getMessageTemplates('consent_reminder', 'email');
    $form['subject']['#default_value'] = !empty($message_template['subject']) ? $message_template['subject'] : '';
    $form['message']['#default_value'] = !empty($message_template['message']) ? $message_template['message'] : '';
    return $form;
  }
}
