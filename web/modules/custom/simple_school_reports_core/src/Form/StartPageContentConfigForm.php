<?php

namespace Drupal\simple_school_reports_core\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\MessageTemplateServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\simple_school_reports_core\Service\StartPageContentServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class StartPageContentConfigForm extends FormBase {
  /**
   * @var \Drupal\simple_school_reports_core\Service\StartPageContentServiceInterface
   */
  protected $startPageContentService;

  /**
   * @var  \Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
   */
  protected $replaceTokenService;


  public function __construct(StartPageContentServiceInterface $start_page_content, ReplaceTokenServiceInterface $token_service) {
    $this->startPageContentService = $start_page_content;
    $this->replaceTokenService = $token_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('simple_school_reports_core.start_page_content_service'),
      $container->get('simple_school_reports_core.replace_token_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'start_page_content_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['start_page_content'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $this->startPageContentService->buildConfigForm($form['start_page_content'], $form_state);
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
    $start_page_content = $form_state->getValue('start_page_content');
    $settings = [];
    foreach ($start_page_content as $wrapper_id => $data) {
      foreach ($data as $key => $value) {
        $settings[$key] = $value;
      }
    }
    Cache::invalidateTags(['start_page_settings']);
    $this->startPageContentService->setStartPageContent($settings);
    $this->messenger()->addStatus($this->t('Configuration has been saved'));
  }
}
