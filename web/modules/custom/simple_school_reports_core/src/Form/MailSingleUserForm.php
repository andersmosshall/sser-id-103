<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for mail single user.
 */
class MailSingleUserForm extends MailMultipleUsersForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_single_users';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Mail user');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    if (!$user) {
      throw new NotFoundHttpException('no user');
    }

    if (!$this->emailService->getUserEmail($user)) {
      throw new AccessDeniedHttpException();
    }

    $this->tempStoreFactory->get('mail_multiple_users')->set($this->currentUser()->id(), [$user]);
    return parent::buildForm($form, $form_state);
  }
}
