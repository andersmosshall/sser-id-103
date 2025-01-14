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
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for cancelling multiple user accounts.
 */
class MailMultipleCaregiversForm extends ConfirmFormBase {

  /**
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface
   */
  protected $emailService;

  /**
   * @var  \Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
   */
  protected $replaceTokenService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MailMultipleCaregiversForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    EmailServiceInterface $email_service,
    ReplaceTokenServiceInterface $replace_token_service,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->emailService = $email_service;
    $this->replaceTokenService = $replace_token_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('simple_school_reports_core.email_service'),
      $container->get('simple_school_reports_core.replace_token_service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_multiple_caregivers_form';
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
    // Retrieve the accounts to be canceled from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $recipient_groups = $this->tempStoreFactory
      ->get('mail_caregivers')
      ->get($this->currentUser()->id());
    if (empty($recipient_groups)) {
      return $this->redirect($this->getCancelRoute());
    }

    $count_recipients = 0;

    $form['recipient_groups_wrapper'] = [
      '#type' => 'details',
      '#open' => count($recipient_groups) <= 10,
    ];
    $student_has_email = FALSE;

    $form['recipient_groups_wrapper']['recipient_groups'] = ['#tree' => TRUE];
    foreach ($recipient_groups as $student_uid => $recipient_group) {
      $recipient_options = [];

      $student_name = $recipient_group['student'];
      if (!empty($recipient_group['student_email'])) {
        $student_has_email = TRUE;
        $student_name .= ' (' . $recipient_group['student_email'] . ')';
        $caregiver_uid = '';
      }

      foreach ($recipient_group['recipients'] as $caregiver_uid => $recipient) {
        $count_recipients++;
        $recipient_options[$recipient['mail'] . '__CGUID__' . $caregiver_uid] = $recipient['full_name'] . ' (' . $recipient['mail'] . ')';
      }
      $form['recipient_groups_wrapper']['recipient_groups'][$student_uid] = [
        '#type' => 'checkboxes',
        '#title' => $student_name . ':',
        '#default_value' => array_keys($recipient_options),
        '#options' => $recipient_options,
      ];

      if (!empty($recipient_group['student_email'])) {
        $form['student_mail_' . $student_uid] = [
          '#type' => 'value',
          '#value' => $recipient_group['student_email'] . '__CGUID__' . $caregiver_uid,
        ];
      }
    }

    $form['recipient_groups_wrapper']['#title'] = $this->t('Recipients') . ' (' . $count_recipients . ')';

    if (empty($count_recipients)) {
      throw new AccessDeniedHttpException();
    }

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $description = '';
    $replace_tokens = $this->replaceTokenService->getReplaceTokenDescriptions([
      ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS,
      ReplaceTokenServiceInterface::RECIPIENT_REPLACE_TOKENS,
      ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS,
    ], TRUE);

    if (!empty($replace_tokens)) {
      $description_lines = ['<b>' . $this->t('Replacement patterns') . ':</b>'];
      foreach ($replace_tokens as $token => $description) {
        $description_lines[] = $token . ' = ' . $description;
      }
      $description = implode('<br>', $description_lines);
    }

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => '',
      '#description' => $description,
      '#required' => TRUE,
    ];

    $form['attachments'] = [
      '#title' => $this->t('Attachments'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://ssr_tmp',
      '#default_value' => NULL,
      '#multiple' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf doc docx jpg jpeg png txt zip'],
        'file_validate_size' => [15 * 1024 * 1024],
      ],
      '#access' => EmailService::supportEmailAttachments(),
    ];

    if ($student_has_email) {
      $form['send_to_students'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Send to students as well'),
        '#description' => count($recipient_groups) > 1
          ? $this->t('A copy of the mail will be sent to relevant students with valid email addresses as well. NOTE: All students in the previously selected list of students, see recipients above, will receive a copy of the mail, even if the corresponding caregivers has been deselected.')
          : $this->t('A copy of the mail will be sent to the student if email addresses is valid. NOTE: The student will receive a copy of the mail, even if the corresponding caregivers has been deselected.'),
        '#default_value' => FALSE,
      ];
    }

    $form['send_copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send me a copy'),
      '#description' => $this->t('A copy of the mail and to whom the mail has been sent to will be sent to you.'),
      '#default_value' => FALSE,
      '#access' => !empty($this->emailService->getUserEmail($this->currentUser())),
    ];

    if ($this->currentUser()->hasPermission('school staff permissions')) {
      $form['extra_recipients_wrapper'] = [
        '#type' => 'details',
        '#title' => $this->t('Extra recipients'),
        '#open' => FALSE,
      ];
      $form['extra_recipients_wrapper']['extra_recipients'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Extra recipients'),
        '#description' => $this->t('Add extra recipients to the mail, one per row. Remember to not expose school related information to unauthorized recipients.'),
        '#default_value' => '',
      ];
    }

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getTriggeringElement()['#name'] === 'attachments_remove_button') {
      return;
    }

    $attachments = $form_state->getValue('attachments');
    $total_file_size = 0;
    $count_files = 0;

    if (!empty($attachments)) {
      /** @var \Drupal\file\FileStorage $file_storage */
      $file_storage = $this->entityTypeManager->getStorage('file');
      foreach ($attachments as $attachment) {
        /** @var \Drupal\file\FileInterface $file */
        $file = $file_storage->load($attachment);
        $total_file_size += (int) $file->getSize();
        $count_files++;
      }
    }

    if ($total_file_size > (15 * 1024 * 1024 + 1024)) {
      $form_state->setErrorByName('attachments', $this->t('Total filesize of all attached files is above @limit', ['@limit' => '15 MB']));
    }

    if ($count_files > 5) {
      $form_state->setErrorByName('attachments', $this->t('You can only attach up to @limit files', ['@limit' => '5']));
    }

    $extra_recipients = $form_state->getValue('extra_recipients');
    if (!empty($extra_recipients)) {
      $emails = array_map('trim', explode(PHP_EOL, $extra_recipients));
      foreach ($emails as $email) {
        if (!empty($email) && !\Drupal::service('email.validator')->isValid($email)) {
          $form_state->setErrorByName('extra_recipients', $this->t('The email address %mail is not valid.', ['%mail' => $email]));
        }
      }
    }
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

      $send_to_students = $form_state->getValue('send_to_students', FALSE);

      $recipient_groups = $form_state->getValue('recipient_groups');
      $send_to = [];
      if (!empty($recipient_groups)) {
        foreach ($recipient_groups as $student_uid => $recipient_group) {
          if ($send_to_students && !empty($form_state->getValue('student_mail_' . $student_uid))) {
            [$email, $caregiver_uid] = explode('__CGUID__', $form_state->getValue('student_mail_' . $student_uid));

            $replace_context = [
              ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS => ['target_id' => $student_uid, 'entity_type' => 'user'],
              ReplaceTokenServiceInterface::RECIPIENT_REPLACE_TOKENS => ['target_id' => $caregiver_uid, 'entity_type' => 'user'],
              ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS => ['target_id' => $uid, 'entity_type' => 'user'],
            ];

            $options = [
              'maillog_student_context' => $student_uid,
              'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_MAIL_USER,
            ];

            $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$email, $subject, $message, $replace_context, $form_state->getValue('attachments'), $options]];
            $send_to[] = $email;
          }

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

                $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$email, $subject, $message, $replace_context, $form_state->getValue('attachments'), $options]];
                $send_to[] = $email;
              }
            }
          }
        }
      }

      $extra_recipients = $form_state->getValue('extra_recipients');
      if (!empty($extra_recipients)) {
        $emails = array_map('trim', explode(PHP_EOL, $extra_recipients));
        foreach ($emails as $email) {
          if (!empty($email) && \Drupal::service('email.validator')->isValid($email)) {
            $replace_context = [];
            $options = [
              'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_OTHER,
            ];
            $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$email, $subject, $message, $replace_context, $form_state->getValue('attachments'), $options]];
            $send_to[] = $email;
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

          $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$email, 'Kopia: ' . $subject, $message, [], $form_state->getValue('attachments'), $options]];
        }
      }

      if (!empty($batch['operations'])) {
        if (count($batch['operations']) < 10) {
          $batch['progressive'] = FALSE;
        }
        else {
          $batch['op_delay'] = 500;
        }

        batch_set($batch);
      }
      else {
        $this->messenger()->addWarning($this->t('No mail has been sent.'));
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
