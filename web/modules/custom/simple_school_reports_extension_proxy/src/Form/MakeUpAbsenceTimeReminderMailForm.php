<?php

namespace Drupal\simple_school_reports_extension_proxy\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simple_school_reports_core\Form\MailMultipleCaregiversForm;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for cancelling multiple user accounts.
 */
class MakeUpAbsenceTimeReminderMailForm extends MailMultipleCaregiversForm {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface
   */
  protected $messageTemplateService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->messageTemplateService = $container->get('simple_school_reports_core.message_template_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'make_up_absence_time_reminder_mail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Mail caregivers');
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
    if (!$this->moduleHandler->moduleExists('simple_school_reports_absence_make_up')) {
      throw new AccessDeniedHttpException();
    }

    $form = parent::buildForm($form, $form_state);

    $message_template = $this->messageTemplateService->getMessageTemplates('absence_make_up', 'email');
    $form['subject']['#default_value'] = !empty($message_template['subject']) ? $message_template['subject'] : '';
    $form['message']['#default_value'] = !empty($message_template['message']) ? $message_template['message'] : '';

    unset($form['attachments']);

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

    if ($form_state->getValue('confirm') && ($subject = $form_state->getValue('subject')) && ($message = $form_state->getValue('message'))) {

      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Sending mails'),
        'init_message' => $this->t('Sending mails'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'finished' => [self::class, 'finished'],
        'operations' => [],
      ];

      $uid = $this->currentUser()->id();

      $recipient_groups = $form_state->getValue('recipient_groups');
      $send_to = [];
      if (!empty($recipient_groups)) {
        foreach ($recipient_groups as $student_uid => $recipient_group) {
          foreach ($recipient_group as $caregiver_data) {
            if ($caregiver_data) {
              [$email, $caregiver_uid] = explode('__CGUID__', $caregiver_data);
              if ($email && $caregiver_uid) {
                $replace_context = [
                  ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS => ['target_id' => $student_uid, 'entity_type' => 'user'],
                  ReplaceTokenServiceInterface::RECIPIENT_REPLACE_TOKENS => ['target_id' => $caregiver_uid, 'entity_type' => 'user'],
                  ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS => ['target_id' => $uid, 'entity_type' => 'user'],
                ];

                $options = [
                  'maillog_student_context' => $student_uid,
                  'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_CAREGIVER,
                ];

                $batch['operations'][] = [[self::class, 'remindMakeUpAbsenceSendMail'], [$email, $subject, $message, $replace_context, $options]];
                $send_to[] = $email;
              }
            }
          }
        }
      }

      if ($form_state->getValue('send_copy', FALSE)) {
        if ($email = $this->emailService->getUserEmail($this->currentUser())) {
          $message .= PHP_EOL;
          $message .= PHP_EOL;

          $message .= 'Mail skickat till:' . PHP_EOL;
          $message .= implode(PHP_EOL, $send_to);

          $options = [
            'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_CAREGIVER,
          ];

          $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$email, 'Kopia: ' . $subject, $message, [], [], $options]];
        }
      }

      if (!empty($batch['operations'])) {
        $batch['op_delay'] = 500;
        batch_set($batch);
      }
    }
  }

  public static function remindMakeUpAbsenceSendMail(string $recipient, string $subject, string $message, array $replace_context, array $options, &$context) {
    if (EmailService::batchSendMail($recipient, $subject, $message, $replace_context, [], $options, $context)) {
      if (!empty($replace_context[ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS]['target_id'])) {
        $student = \Drupal::entityTypeManager()->getStorage('user')->load($replace_context[ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS]['target_id']);
        if ($student) {
          $today = new DrupalDateTime();
          $today->setTime(0,0);
          $student->set('field_make_up_time_reminded', $today->getTimestamp());
          $student->save();
        }
      }
    }
  }

  public static function finished($success, $results) {
    if (!$success || empty($results['mail_sent'])) {
      \Drupal::messenger()->addError(t('Something went wrong'));
      return;
    }

    \Drupal::messenger()->addStatus(t('@count mails has been sent.', ['@count'  => count($results['mail_sent'])]));
  }
}
