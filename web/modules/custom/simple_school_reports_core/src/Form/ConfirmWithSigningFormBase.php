<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_entities\SsrSigningInterface;
use Drupal\simple_school_reports_maillog\SsrMaillogInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form with signing.
 */
abstract class ConfirmWithSigningFormBase extends ConfirmFormBase {

  public function __construct(
    protected SessionInterface $session,
    protected EmailServiceInterface $emailService,
    protected UuidInterface $uuidService,
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('session'),
      $container->get('simple_school_reports_core.email_service'),
      $container->get('uuid'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }


  /**
   * @return string
   */
  protected function getSigningType(): string {
    return 'email';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Continue');
  }

  /**
   * {@inheritdoc}
   */
  protected function getFinalSubmitText() {
    return $this->t('Save');
  }

  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL, $user = NULL) {
    if (!$this->emailService->getUserEmail($this->currentUser)) {
      throw new AccessDeniedHttpException();
    }

    if ($form_state->get('step') === 'signing') {
      if (!$form_state->get('sign_template')) {
        throw new \RuntimeException('no sign template');
      }

      $form['sign_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Confirm'),
      ];

      $form['sign_template'] = [
        '#markup' => $form_state->get('sign_template'),
      ];

      $form = parent::buildForm($form, $form_state);
      $form['actions']['submit']['#value'] = $this->getFinalSubmitText();

      return match ($this->getSigningType()) {
        'email' => $this->buildEmailSigningForm($form, $form_state),
        default => throw new \RuntimeException('invalid signing type'),
      };
    }

    $form = parent::buildForm($form, $form_state);

    $args = func_get_args();
    $args[0] = $form;

    $form = call_user_func_array([$this, 'beforeSigningBuildForm'], $args);
    $form['actions']['submit']['#submit'] = ['::beforeSigningSubmit'];
    return $form;
  }

  abstract protected function beforeSigningBuildForm($form, $form_state): array;

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  private function buildEmailSigningForm(array $form, FormStateInterface $form_state) {

    $sign_id = $form_state->get('sign_id');
    if (!$sign_id) {
      $sign_id = $this->uuidService->generate();
      $form_state->set('sign_id', $sign_id);
    }

    $form['sign_id'] = [
      '#type' => 'value',
      '#value' => $sign_id,
    ];

    $form['confirm_sign'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have red and confirm the text above'),
      '#required' => TRUE,
    ];


    if ($this->session->get('ssr_email_signing_safe')) {
      $form['sign_key_confirm'] = [
        '#type' => 'value',
        '#value' => $form_state->get('sign_key'),
      ];
    }
    else {
      $form['sign_key_confirm'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Enter the security key'),
        '#description' => $this->t('A mail has been sent to @email with a security key', ['@email' => $this->currentUser->getEmail()]),
        '#required' => TRUE,
        '#attributes' => [
          'autocomplete' => 'off',
        ],
      ];
    }

    return $form;
  }

  private function setSafeFormValues(FormStateInterface $form_state, array $safe_form_values) {
    $form_state->set('safe_form_values', $safe_form_values);
  }

  private function getSafeFormValues(FormStateInterface $form_state): array {
    return $form_state->get('safe_form_values') ?? [];
  }

  /**
   * @param array $safe_form_values
   *
   * @return string
   *   Sign template as html string.
   */
  abstract protected function makeSignTemplate(array $safe_form_values, FormStateInterface $form_state): string;

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function beforeSigningSubmit(array $form, FormStateInterface $form_state) {
    $safe_form_values = $form_state->getValues();
    $this->setSafeFormValues($form_state, $safe_form_values);
    $sign_template = $this->makeSignTemplate($safe_form_values, $form_state);

    if (!$sign_template) {
      return;
    }

    $form_state->set('sign_template', $sign_template);
    $form_state->set('step', 'signing');

    $sign_key = mt_rand(123456, 999999);
    $form_state->set('sign_key', $sign_key);

    if ($this->getSigningType() === 'email' && !$this->session->get('ssr_email_signing_safe')) {
      $replace_context = [];

      $options = [
        'maillog_mail_type' => SsrMaillogInterface::MAILLOG_TYPE_INFRASTRUCTURE,
        'no_reply_to' => TRUE,
      ];

      $school_name = Settings::get('ssr_school_name') ?? $this->currentUser->getDisplayName();
      $formatted_sign_key = number_format($sign_key, 0, ',', ' ');

      $subject = $this->t('@school_name security key', ['@school_name' => $school_name]);
      $message = $this->t('A security key is requested for @school_name: @key', ['@school_name' => $school_name, '@key' => $formatted_sign_key]);
      $context = [];

      $sent = EmailService::batchSendMail($this->currentUser->getEmail(), $subject, $message, $replace_context, [], $options, $context);
      if (!$sent) {
        $this->messenger()->addError($this->t('Something went wrong.'));
        return;
      }
      if ($this->currentUser->id() == 1) {
        $this->messenger()->addStatus($sign_key);
      }
    }

    $form_state->setRebuild(TRUE);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->get('step') === 'signing') {
      if (str_replace(' ', '', $form_state->getValue('sign_key_confirm', 'not_set')) !== (string) $form_state->get('sign_key')) {
        $sign_tries = $form_state->get('sign_tries') ?? 0;
        $sign_tries++;
        $form_state->set('sign_tries', $sign_tries);
        if ($sign_tries >= 5) {
          throw new AccessDeniedHttpException();
        }

        $form_state->setErrorByName('sign_key_confirm', $this->t('Invalid security code'));
      }

      if (!$form_state->getValue('sign_id')) {
        $form_state->setRebuild(TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('step') === 'signing') {
      // Double check stuff.
      if (str_replace(' ', '', $form_state->getValue('sign_key_confirm', 'not_set')) !== (string) $form_state->get('sign_key') || !$form_state->getValue('confirm_sign') || !$form_state->getValue('sign_id')) {
        $this->messenger()->addError($this->t('Something went wrong.'));
        return;
      }

      if ($this->getSigningType() === 'email') {
        $this->session->set('ssr_email_signing_safe', TRUE);
      }

      $now = new \DateTime();
      $signing_values = [
        'id' => $form_state->getValue('sign_id'),
        'label' => 'Signering av ' . $this->currentUser->getDisplayName() . ' ' . $now->format('Y-m-d H:i:s'),
        'sign_type' => $this->getSigningType(),
        'field_sign_template' => $form_state->get('sign_template'),
        'field_hidden_data' => json_encode($this->getSafeFormValues($form_state)),
        'uid' => ['target_id' => $this->currentUser->id()],
      ];

      /** @var \Drupal\simple_school_reports_entities\SsrSigningInterface $signing */
      $signing = $this->entityTypeManager->getStorage('ssr_signing')->create($signing_values);
      $signing->save();
      $this->afterSigningSubmit($form, $form_state, $this->getSafeFormValues($form_state), $signing);
    }
  }

  abstract protected function afterSigningSubmit(array &$form, FormStateInterface $form_state, array $safe_form_values, SsrSigningInterface $signing);
}
