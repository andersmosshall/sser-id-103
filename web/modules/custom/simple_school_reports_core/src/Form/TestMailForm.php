<?php

namespace Drupal\simple_school_reports_core\Form;

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
 * Provides a confirmation form for test mail functionality.
 */
class TestMailForm extends ConfirmFormBase {

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
    EmailServiceInterface $email_service,
    ReplaceTokenServiceInterface $replace_token_service,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->emailService = $email_service;
    $this->replaceTokenService = $replace_token_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.email_service'),
      $container->get('simple_school_reports_core.replace_token_service'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_mail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Test mail');
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

    if (empty(Settings::get('ssr_bug_report_email'))) {
      $this->messenger()->addError('Missing ssr_bug_report_email setting!');
    }

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => 'Test mail ÅÄÖ åäö ü é end',
      '#required' => TRUE,
    ];

    $default_message = 'Test mail ÅÄÖ åäö ü é end' . PHP_EOL . PHP_EOL;
    $default_message .= 'New row!' . PHP_EOL . PHP_EOL;

    $description = '';
    $replace_tokens = $this->replaceTokenService->getReplaceTokenDescriptions(['ALL'], TRUE);

    if (!empty($replace_tokens)) {
      $description_lines = ['<b>' . $this->t('Replacement patterns') . ':</b>'];
      foreach ($replace_tokens as $token => $description) {
        $description_lines[] = $token . ' = ' . $description;
        $default_message .= $description . ': ' . $token . PHP_EOL;
      }
      $description = implode('<br>', $description_lines);
    }

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $default_message,
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

    return parent::buildForm($form, $form_state);
  }

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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = Settings::get('ssr_bug_report_email');
    if (!$email) {
      $this->messenger()->addError('Missing ssr_bug_report_email setting!');
      return;
    }

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

      /** @var \Drupal\user\UserStorageInterface $user_storage */
      $user_storage =  $this->entityTypeManager->getStorage('user');

      /** @var \Drupal\user\UserInterface $student */
      $student = $user_storage->create([
        'name' => 'Mail Test Student',
        'field_first_name' => 'Student_Firstname',
        'field_last_name' => 'Student_Lastname',
        'field_invalid_absence' => 1234,
      ]);
      $student->set('roles', ['student']);
      $student->setEmail('mail_test_student@example.com');

      /** @var \Drupal\user\UserInterface $caregiver */
      $caregiver = $user_storage->create([
        'name' => 'Mail Test Caregiver',
        'field_first_name' => 'Caregiver_Firstname',
        'field_last_name' => 'Caregiver_Lastname',
      ]);
      $caregiver->set('roles', ['caregiver']);
      $caregiver->setEmail('mail_test_caregiver@example.com');

      /** @var \Drupal\node\NodeStorageInterface $node_storage */
      $node_storage = $this->entityTypeManager->getStorage('node');

      /** @var \Drupal\node\NodeInterface $attendance_report */
      $attendance_report = $node_storage->create([
        'type' => 'course_attendance_report',
        'title' => 'Course 2001-01-01 01:01 (11 min)',
      ]);

      $replace_context = [
        ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS => $student,
        ReplaceTokenServiceInterface::RECIPIENT_REPLACE_TOKENS => $caregiver,
        ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS => ['target_id' => $this->currentUser()->id(), 'entity_type' => 'user'],
        ReplaceTokenServiceInterface::ATTENDANCE_REPORT_TOKENS => $attendance_report,
        ReplaceTokenServiceInterface::INVALID_ABSENCE_TOKENS => '123',
      ];

      $options = [
        'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_TEST,
      ];
      $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$email, $subject, $message, $replace_context, $form_state->getValue('attachments'), $options]];

      if (!empty($batch['operations'])) {
        $batch['op_delay'] = 500;
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
