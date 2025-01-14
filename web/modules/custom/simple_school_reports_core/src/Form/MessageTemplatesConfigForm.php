<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class MessageTemplatesConfigForm extends FormBase {

  /**
   * @var \Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface
   */
  protected $messageTemplateService;

  /**
   * @var  \Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
   */
  protected $replaceTokenService;


  public function __construct(MessageTemplateServiceInterface $message_template_service, ReplaceTokenServiceInterface $token_service) {
    $this->messageTemplateService = $message_template_service;
    $this->replaceTokenService = $token_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.message_template_service'),
      $container->get('simple_school_reports_core.replace_token_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'message_templates_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['message_templates'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $this->messageTemplateService->buildConfigForm($form['message_templates'], $form_state);
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message_templates = $form_state->getValue('message_templates');
    $this->messageTemplateService->setMessageTemplates($message_templates);
    $this->messenger()->addStatus($this->t('Configuration has been saved'));
  }
}
