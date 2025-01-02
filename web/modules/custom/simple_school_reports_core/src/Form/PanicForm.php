<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
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
 * Provides a confirmation form for panic form.
 */
class PanicForm extends ConfirmFormBase {

  /**
   * @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface
   */
  protected $emailService;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new MailMultipleCaregiversForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(
    EmailServiceInterface $email_service,
    ModuleHandlerInterface $module_handler,
    StateInterface $state
  ) {
    $this->emailService = $email_service;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.email_service'),
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'panic_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to set the site into maintenance mode?');
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
    return $this->t('All users (including yourself) will be logged out and the site will be shut down into maintenance mode. A bug report mail will automatically be sent to the system administrator.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Please describe the reason why you want to shut down the site to maintenance mode.'),
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Issue'),
      '#default_value' => '',
      '#description' => $this->t('Describe the issue as detailed as possible.'),
      '#required' => TRUE,
      '#maxlength' => 2000,
    ];

    $form = parent::buildForm($form, $form_state);

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

    if ($form_state->getValue('confirm') && ($message = $form_state->getValue('message'))) {
      $ssr_bug_report_email = Settings::get('ssr_bug_report_email', NULL);

      if (!$ssr_bug_report_email) {
        $this->messenger()->addWarning($this->t('Something went wrong'));
        return;
      }

      if ($this->moduleHandler->moduleExists('simple_school_reports_logging')) {
        /** @var \Drupal\simple_school_reports_logging\Service\RequestLogServiceInterface $service */
        $service = \Drupal::service('simple_school_reports_logging.request_log');

        $request_log_message = $service->getRequestLogMessage(FALSE, TRUE);
        $message .= PHP_EOL . PHP_EOL . PHP_EOL . $request_log_message;
      }

      $context = [];
      EmailService::batchSendMail($ssr_bug_report_email, $this->t('PANIC! @name has been set into maintenance mode.', ['@name' => Settings::get('ssr_school_name', '?')]), $message, [], [], [], $context);
      $this->state->set('system.maintenance_mode', TRUE);
      return;
    }
    $this->messenger()->addWarning($this->t('Something went wrong'));
  }
}
