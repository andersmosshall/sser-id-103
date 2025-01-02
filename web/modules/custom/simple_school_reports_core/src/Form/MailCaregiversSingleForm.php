<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirmation form for mail single students caregivers.
 */
class MailCaregiversSingleForm extends MailMultipleCaregiversForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_caregivers_single_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    if (!$user || !$user->hasRole('student')) {
      throw new NotFoundHttpException('no student user');
    }

    $recipient_data = $this->emailService->getCaregiverRecipients($user->id());
    if (empty($recipient_data)) {
      $this->messenger()->addWarning($this->t('@student misses caregiver(s) with email address set.', ['@student' => $user->getDisplayName()]));
      throw new NotFoundHttpException('no student user');
    }


    $recipient_data = [];
    /** @var \Drupal\user\UserInterface $user */
    $recipient_data[$user->id()] = [
      'student' => $user->getDisplayName(),
      'student_email' => $this->emailService->getUserEmail($user),
      'recipients' => $this->emailService->getCaregiverRecipients($user->id()),
    ];
    $this->tempStoreFactory->get('mail_caregivers')->set($this->currentUser()->id(), $recipient_data);
    return parent::buildForm($form, $form_state);
  }
}
