<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MessageTemplateService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class MessageTemplateService implements MessageTemplateServiceInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var  \Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
   */
  protected $replaceTokenService;


  public function __construct(
    StateInterface $state,
    ModuleHandlerInterface $module_handler,
    ReplaceTokenServiceInterface $replace_token_service
  ) {
    $this->state = $state;
    $this->moduleHandler = $module_handler;
    $this->replaceTokenService = $replace_token_service;
  }


  public function getMessageTemplates(string $category = 'ALL', string $type = 'ALL'): ?array {
    $templates = $this->state->get('ssr_message_templates', []);
    $cat_all = $category === 'ALL';
    $type_all = $type === 'ALL';
    $school_name = Settings::get('ssr_school_name', '');
    $default_templates = [];
    $default_templates['attendance_report'] = [
      'email' => [
        'subject' => 'Frånvaro från [L]',
        'message' => 'Frånvaro på [T] minuter har rapporterats till [L] för [E].' . PHP_EOL . PHP_EOL . 'Med vänliga hälsningar' . PHP_EOL . $school_name,
      ],
    ];

    $this->moduleHandler->alter('default_message_templates', $default_templates);

    foreach ($default_templates as $default_category => $category_templates) {
      if (!isset($templates[$default_category])) {
        $templates[$default_category] = $category_templates;
      }
      else {
        foreach ($category_templates as $template_type => $template_value) {
          if (!isset($templates[$default_category][$template_type])) {
            $templates[$default_category][$template_type] = $template_value;
          }
        }
      }
    }

    if ($cat_all) {
      return $templates;
    }
    $templates = !empty($templates[$category]) ? $templates[$category] : [];
    if ($type_all) {
      return $templates;
    }
    if (!empty($templates[$type])) {
      return $templates[$type];
    }
    return NULL;
  }

  public function setMessageTemplates(array $templates) {
    $this->state->set('ssr_message_templates', $templates);
  }

  protected function getCategoryNameMap(): array {
    $category_name_map = [
      'attendance_report' => $this->t('Attendance report'),
    ];

    return $category_name_map;
  }

  protected function getCategoryDescriptionMap(): array {
    $description = '';
    $replace_tokens = $this->replaceTokenService->getReplaceTokenDescriptions([
      ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS,
      ReplaceTokenServiceInterface::RECIPIENT_REPLACE_TOKENS,
      ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS,
      ReplaceTokenServiceInterface::INVALID_ABSENCE_TOKENS,
      ReplaceTokenServiceInterface::ATTENDANCE_REPORT_TOKENS,
    ], TRUE);

    if (!empty($replace_tokens)) {
      $description_lines = ['<b>' . $this->t('Replacement patterns') . ':</b>'];
      foreach ($replace_tokens as $token => $description) {
        $description_lines[] = $token . ' = ' . $description;
      }
      $description = implode('<br>', $description_lines);
    }


    $category_description_map = [
      'attendance_report' => $description,
    ];

    return $category_description_map;
  }



  public function buildConfigForm(array &$form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $category_name_map = $this->getCategoryNameMap();
    $category_description_map = $this->getCategoryDescriptionMap();

    $templates = $this->getMessageTemplates();
    foreach ($templates as $category => $category_templates) {
      if (empty($category_name_map[$category])) {
        continue;
      }

      $form[$category] = [
        '#type' => 'details',
        '#title' => $category_name_map[$category],
        '#tree' => TRUE,
        '#open' => TRUE,
      ];

      foreach ($category_templates as $type => $values) {
        if ($type === 'email') {
          $form[$category]['email']['subject'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Subject'),
            '#default_value' => isset($values['subject']) ? $values['subject'] : '',
            '#required' => TRUE,
          ];
          $form[$category]['email']['message'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Message'),
            '#default_value' => isset($values['message']) ? $values['message'] : '',
            '#description' => !empty($category_description_map[$category]) ? $category_description_map[$category] : NULL,
            '#required' => TRUE,
          ];
        }
      }
    }

    $this->moduleHandler->alter('message_templates_config_form', $form, $form_state, $templates);
  }


}
