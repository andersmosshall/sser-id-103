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
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for mail multiple users.
 */
class MailMultipleUsersForm extends ConfirmFormBase {

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
   * @var \Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface
   */
  protected $messageTemplateService;

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
    MessageTemplateServiceInterface $message_template_service,
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->emailService = $email_service;
    $this->replaceTokenService = $replace_token_service;
    $this->messageTemplateService = $message_template_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('simple_school_reports_core.email_service'),
      $container->get('simple_school_reports_core.replace_token_service'),
      $container->get('simple_school_reports_core.message_template_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_multiple_users';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Mail users');
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
    // Retrieve the accounts to be canceled from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('mail_multiple_users')
      ->get($this->currentUser()->id());
    if (empty($accounts)) {
      return $this->redirect($this->getCancelRoute());
    }

    $names = [];

    $form['accounts'] = ['#tree' => TRUE];
    foreach ($accounts as $account) {
      $uid = $account->id();
      // Prevent user 1 from being canceled.
      if ($uid <= 1) {
        continue;
      }

      $names[$uid] = $account->getDisplayName() . ' (' . $account->getEmail() . ')';
      $form['accounts'][$uid] = [
        '#type' => 'value',
        '#value' => $account->getEmail(),
      ];
    }

    $form['account']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
    ];

    if (empty($names)) {
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

    $form['send_copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send me a copy'),
      '#description' => $this->t('A copy of the mail and to whom the mail has been sent to will be sent to you.'),
      '#default_value' => FALSE,
      '#access' => !empty($this->emailService->getUserEmail($this->currentUser())),
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

    if ($form_state->getValue('confirm') && ($subject = $form_state->getValue('subject')) && ($message = $form_state->getValue('message'))) {

      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Sending mails'),
        'init_message' => $this->t('Sending mails'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
        'finished' => [self::class, 'finished'],
      ];

      $send_to = [];
      foreach ($form_state->getValue('accounts') as $uid => $email) {
        $replace_context = [
          ReplaceTokenServiceInterface::RECIPIENT_REPLACE_TOKENS => ['target_id' => $uid, 'entity_type' => 'user'],
          ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS => ['target_id' => $this->currentUser()->id(), 'entity_type' => 'user'],
        ];

        $options = [
          'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_MAIL_USER,
        ];

        $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$email, $subject, $message, $replace_context, [], $options]];
        $send_to[] = $email;
      }

      if ($form_state->getValue('send_copy', FALSE)) {
        if ($email = $this->emailService->getUserEmail($this->currentUser())) {
          $message .= PHP_EOL;
          $message .= PHP_EOL;

          $message .= 'Mail skickat till:' . PHP_EOL;
          $message .= implode(PHP_EOL, $send_to);

          $options = [
            'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_OTHER,
          ];

          $batch['operations'][] = [[EmailService::class, 'batchSendMail'], [$email, 'Kopia: ' . $subject, $message, [], [], $options]];
        }
      }

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
