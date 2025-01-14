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
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for MailMultipleResetPasswordForm accounts.
 */
class MailMultipleResetPasswordForm extends ConfirmFormBase {

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
   * Constructs a new MailMultipleCaregiversForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    EmailServiceInterface $email_service,
    ReplaceTokenServiceInterface $replace_token_service
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->emailService = $email_service;
    $this->replaceTokenService = $replace_token_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('simple_school_reports_core.email_service'),
      $container->get('simple_school_reports_core.replace_token_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_multiple_reset_password_form';
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
    return $this->t('Users will get an mail with instructions to set a password for future logins. (Note, if any of the user in this list is caregivers, they will be set to allow login.)');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the accounts to be canceled from the temp store.
    /** @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('multiple_password_reset')
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
        '#value' => $names[$uid],
      ];
    }

    $form['account']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
    ];

    if (empty($names)) {
      throw new AccessDeniedHttpException();
    }

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

      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Sending mails'),
        'init_message' => $this->t('Sending mails'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
        'finished' => [self::class, 'finished'],
      ];

      foreach ($form_state->getValue('accounts') as $uid => $value) {
        $batch['operations'][] = [[EmailService::class, 'batchSendPasswordReset'], [$uid]];
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

  public static function finished($success, array $results) {
    if ($success) {
      \Drupal::messenger()->addStatus('Done');
    }
    else {
      \Drupal::messenger()->addError('Something went wrong.');
    }

  }
}
