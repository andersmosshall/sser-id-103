<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Drupal\symfony_mailer\Address;
use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorage;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TermService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class EmailService implements EmailServiceInterface {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\Core\Mail\MailManager
   */
  protected $mailManager;

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\symfony_mailer\EmailFactoryInterface|null
   */
  protected $emailFactory;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface;
   */
  protected $logger;

  protected $uidRecipientMap;

  protected $uidCaregiverRecipientMap;

  protected int|null $mailCount = NULL;

  /**
   * @param \Drupal\Core\Database\Connection $connection
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    Connection $connection,
    EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack,
    MailManagerInterface $mail_manager,
    AccountInterface $current_user,
    ?EmailFactoryInterface $email_factory,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->mailManager = $mail_manager;
    $this->currentUser = $entity_type_manager->getStorage('user')->load($current_user->id());
    $this->emailFactory = $email_factory ?? NULL;
    $this->logger = $logger;
  }

  public function skipMail(?string $mail) : bool {
    if ($mail) {
      $prefix = 'no-reply-';
      $is_dev = !str_contains($this->currentRequest->getHost(), 'simpleschoolreports.se');
      $suffix = $is_dev
        ? str_replace('.', '\.', $this->currentRequest->getHost())
        : '\.simpleschoolreports\.se';
      $preg_match = $prefix . '.+' . $suffix;
      return (bool) preg_match('/' . $preg_match . '/', $mail);
    }
    return TRUE;
  }

  protected function getUidRecipientMap() : array {
    if (!is_array($this->uidRecipientMap)) {
      $map = [];

      $query = $this->connection->select('users_field_data', 'u');
      $query->innerJoin('user__field_first_name', 'fn', 'fn.entity_id = u.uid');
      $query->innerJoin('user__field_last_name', 'ln', 'ln.entity_id = u.uid');
      $query->condition('u.status', 1)
        ->fields('u',['uid', 'mail'])
        ->fields('fn',['field_first_name_value'])
        ->fields('ln',['field_last_name_value']);
      $results = $query->execute();

      if (!empty($results)) {
        foreach ($results as $result) {
          if (!$this->skipMail($result->mail)) {
            $map[$result->uid] = [
              'mail' => $result->mail,
              'first_name' => $result->field_first_name_value,
              'last_name' => $result->field_last_name_value,
              'full_name' => $result->field_first_name_value . ' ' . $result->field_last_name_value,
            ];
          }
        }
      }
      $this->uidRecipientMap = $map;
    }

    return $this->uidRecipientMap;
  }

  protected function getUidCaregiverRecipientMap() : array {
    if (!is_array($this->uidCaregiverRecipientMap)) {
      $map = [];
      $query = $this->connection->select('user__field_caregivers', 'c')->fields('c',['field_caregivers_target_id', 'entity_id']);
      $results = $query->execute();
      if (!empty($results)) {
        $recipient_map = $this->getUidRecipientMap();

        foreach ($results as $result) {
          if (!empty($recipient_map[$result->field_caregivers_target_id])) {
            $map[$result->entity_id][$result->field_caregivers_target_id] = $recipient_map[$result->field_caregivers_target_id];
          }
        }
      }
      $this->uidCaregiverRecipientMap = $map;
    }

    return $this->uidCaregiverRecipientMap;
  }

  public function getCaregiverRecipients(int $studentId) : ?array {
    if (isset($this->getUidCaregiverRecipientMap()[$studentId])) {
      return $this->getUidCaregiverRecipientMap()[$studentId];
    }

    return NULL;
  }

  public function getUserEmail(AccountInterface $user) : ?string {
    $email = $user->getEmail();
    if (!$this->skipMail($email)) {
      return $email;
    }
    return NULL;
  }

  public function getUserByEmail(string $email): ?AccountInterface {
    if ($this->skipMail($email)) {
      return NULL;
    }
    $account = current($this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]));
    if ($account instanceof AccountInterface) {
      return $account;
    }
    return NULL;
  }

  public static function supportEmailAttachments(): bool {
    return \Drupal::moduleHandler()->moduleExists('simple_school_reports_email_attachments');
  }

  public function getMailCount(): int {
    if ($this->mailCount === NULL) {
      // Count mail last 2 seconds.
      $this->mailCount = \Drupal::entityTypeManager()->getStorage('ssr_maillog')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', time() - 2, '>')
        ->count()
        ->execute();
    }

    return $this->mailCount;
  }

  public function mailCountIncrement() {
    $this->mailCount = $this->getMailCount() + 1;
  }

  public function resetMailCount() {
    $this->mailCount = NULL;
  }

  public function sendMail(string $recipient, string $subject, string $message, array $options = [], $attachments = []) : bool {
    $include_reply_to = $this->currentUser->id() > 1;
    if (!empty($options['no_reply_to'])) {
      $include_reply_to = FALSE;
    }

    if ($include_reply_to) {
      $from = $this->currentUser->getDisplayName();
      $message_suffix = PHP_EOL . PHP_EOL . $this->t('This mail can not be answered. To reply send mail to @name @mail', ['@name' => $this->currentUser->getDisplayName(), '@mail' => $this->currentUser->getEmail()]);
    }
    else {
      $from = Settings::get('ssr_school_name');
      $message_suffix = '';
    }

    if ($this->emailFactory === NULL) {
      return FALSE;
    }

    $email = $this->emailFactory->newTypedEmail('simple_school_reports_core', 'plaintext');
    $email->setTo($recipient);

    $from_adr = new Address('no-reply@simpleschoolreports.se', $from);
    $email->setFrom($from_adr);
    $email->setSubject(strip_tags($subject));

    $message = strip_tags($message) . $message_suffix;

    $mail_message_string = '<p>' . nl2br($message) . '</p>';
    $mail_message_string = str_replace(PHP_EOL, '', $mail_message_string);
    $mail_message = [
      '#markup' => $mail_message_string,
    ];
    $email->setBody($mail_message);

    $attachments_list_string = [];
    if (!empty($attachments) && self::supportEmailAttachments()) {
      foreach ($attachments as $attachment) {
        try {
          /** @var \Drupal\file\FileInterface $file */
          $file = $this->entityTypeManager->getStorage('file')->load($attachment);
        }
        catch (\Exception $e) {
        }

        if (empty($file)) {
          return FALSE;
        }
        $email->attachFromPath($file->createFileUrl(FALSE), $file->getFilename(), $file->getMimeType());
        $attachments_list_string[] = $file->getFilename();
      }
    }
    // Suppress auto reply.
    $email->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
    $this->mailCountIncrement();

    $is_dev = !str_contains($this->currentRequest->getHost(), 'simpleschoolreports.se') || str_ends_with($recipient, '@example.com');
    if ($is_dev || $email->send()) {
      try {
        /** @var \Drupal\simple_school_reports_maillog\SsrMaillogInterface $maillog */
        $maillog = \Drupal::entityTypeManager()->getStorage('ssr_maillog')->create([
          'label' => 'Maillog',
          'status' => TRUE,
        ]);
        $recipient_user = $this->getUserByEmail($recipient);
        if ($recipient_user) {
          $maillog->set('recipient_user', $recipient_user);
        }
        $maillog->set('recipient_email', $recipient);
        $maillog->set('field_subject', $email->getSubject());
        $maillog->set('field_body',  [
          'value' => $mail_message_string,
          'format' => 'full_html',
        ]);

        if (!empty($options['maillog_student_context'])) {
          $maillog->set('student_context', ['target_id' => $options['maillog_student_context']]);
        }

        if (!empty($attachments_list_string)) {
          $maillog->set('attachments', $attachments_list_string);
        }

        $maillog->set('mail_type', $options['maillog_mail_type'] ?? SsrMaillogInterface::MAILLOG_TYPE_OTHER);
        $maillog->set('created', time());
        $maillog->set('changed', time());
        $maillog->save();
      }
      catch (\Exception $e) {
        // Ignore.
      }

      return TRUE;
    }

    $this->logger->get('simple_school_reports_core')
      ->warning('Failed to send mail %email, message: %message, attachments: %attachments', [
        '%email' => $recipient,
        '%message' => $message,
        '%attachments' => implode(', ', $attachments_list_string),
      ]);

    return FALSE;
  }

  public static function batchSendMail(string $recipient, string $subject, string $message, array $replace_context, array $attachments, array $options, &$context): bool {
    /** @var EmailServiceInterface $email_service */
    $email_service = \Drupal::service('simple_school_reports_core.email_service');
    /** @var \Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface $replace_token_service */
    $replace_token_service = \Drupal::service('simple_school_reports_core.replace_token_service');

    $subject = $replace_token_service->handleText($subject, $replace_context);
    $message = $replace_token_service->handleText($message, $replace_context);

    $mail_id = sha1(json_encode([$recipient, $subject, $message]));
    if (!empty($context['results']['mail_sent'][$mail_id])) {
      return TRUE;
    }

    $sent = $email_service->sendMail($recipient, $subject, $message, $options, $attachments);
    if ($email_service->getMailCount() > 40) {
      $context['results']['break'] = TRUE;
    }

    if ($sent) {
      $context['results']['mail_sent'][$mail_id] = TRUE;
      return TRUE;
    }
    else {
      \Drupal::messenger()->addWarning(t('Could not send mail to @mail for unknown reason.', ['@mail' => $recipient]));
    }
    return FALSE;
  }

  public static function batchSendPasswordReset(string $recipient_uid, &$context): bool {

    $mail_id = sha1(json_encode([$recipient_uid]));
    if (!empty($context['results']['mail_sent'][$mail_id])) {
      return TRUE;
    }

    /** @var EmailServiceInterface $email_service */
    $email_service = \Drupal::service('simple_school_reports_core.email_service');

    /** @var UserInterface $account */
    $account = \Drupal::entityTypeManager()->getStorage('user')->load($recipient_uid);
    if (!$account) {
      \Drupal::messenger()->addWarning(t('Could not send mail to @mail for unknown reason.', ['@mail' => '?']));
      return FALSE;
    }
    if (!$email_service->getUserEmail($account)) {
      \Drupal::messenger()->addWarning(t('Could not send mail to @mail since valid mail is missing.', ['@mail' => $account->getDisplayName()]));
      return FALSE;
    }

    $has_field_allow_login = $account->get('field_allow_login')->value;
    if ($account->hasRole('caregiver') && !$has_field_allow_login) {
      $account->set('field_allow_login', TRUE);
      $account->save();
    }

    $mail = _user_mail_notify('password_reset', $account);

    if ($email_service->getMailCount() > 40) {
      $context['results']['break'] = TRUE;
    }
    if (!empty($mail)) {
      $context['results']['mail_sent'][$mail_id] = TRUE;
      /** @var LoggerChannelFactoryInterface $logger */
      $logger = \Drupal::service('logger.factory');
      $logger->get('simple_school_reports_core')
        ->notice('Password reset instructions mailed to %name at %email.', [
          '%name' => $account->getDisplayName(),
          '%email' => $account->getEmail(),
        ]);
    }
    else {
      \Drupal::messenger()->addWarning(t('Could not send mail to @mail for unknown reason.', ['@mail' => $account->getEmail()]));

      if ($account->hasRole('caregiver') && !$has_field_allow_login) {
        $account->set('field_allow_login', FALSE);
        $account->save();
      }

      return FALSE;
    }
    return TRUE;
  }
}
