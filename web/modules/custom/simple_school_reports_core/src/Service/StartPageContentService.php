<?php

namespace Drupal\simple_school_reports_core\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class StartPageContentService
 *
 * @package Drupal\simple_school_reports_core\Service
 */
class StartPageContentService implements StartPageContentServiceInterface {

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

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $current_user;


  public function __construct(
    StateInterface $state,
    ModuleHandlerInterface $module_handler,
    ReplaceTokenServiceInterface $replace_token_service,
    AccountInterface $current_user
  ) {
    $this->state = $state;
    $this->moduleHandler = $module_handler;
    $this->replaceTokenService = $replace_token_service;
    $this->current_user =  $current_user;
  }


  public function getStartPageContent(string $type = 'ALL') {
    $contents = $this->state->get('ssr_start_page_content', []);

    $school_name = Settings::get('ssr_school_name', '');
    $default_content = [];
    $default_content['default'] = [
      'value' => '<h2>Hej [AF]!</h2><p>Välkommen till Simple School Reports för ' .  $school_name . '.</p><p>Här kan du som skolpersonal hantera kurser, frånvaro och annan elevadministration.</p>',
      'format' => 'full_html',
    ];

    $this->moduleHandler->alter('default_start_page_content', $default_content);

    foreach ($default_content as $content_type => $fallback) {
      $contents[$content_type] = $contents[$content_type] ?? $fallback;
    }

    if ($type === 'ALL') {
      return $contents;
    }

    return $contents[$type] ?? NULL;
  }

  public function getFormattedStartPageContent(string $type): string {
    $return = '';
    $content = $this->getStartPageContent($type);
    if ($content && !empty($content['value'])) {
      $format = $content['format'] ?? 'full_html';
      $return = check_markup($content['value'], $format);

      $replace_context = [
        ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS => ['target_id' => $this->current_user->id(), 'entity_type' => 'user'],
      ];

      $return = $this->replaceTokenService->handleText($return, $replace_context);
    }

    return $return;
  }

  public function setStartPageContent(array $contents) {
    $this->state->set('ssr_start_page_content', $contents);
  }

  public function buildConfigForm(array &$form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $contents = $this->getStartPageContent();

    $category_name_map = [
      'default' => $this->t('School staff start page'),
    ];

    $description = '';
    $replace_tokens = $this->replaceTokenService->getReplaceTokenDescriptions([
      ReplaceTokenServiceInterface::CURRENT_USER_REPLACE_TOKENS,
    ], TRUE);

    if (!empty($replace_tokens)) {
      $description_lines = ['<b>' . $this->t('Replacement patterns') . ':</b>'];
      foreach ($replace_tokens as $token => $description) {
        $description_lines[] = $token . ' = ' . $description;
      }
      $description = implode('<br>', $description_lines);
    }

    foreach ($contents as $type => $content) {
      if (empty($category_name_map[$type])) {
        continue;
      }
      $form[$type . '_wrapper'] = [
        '#type' => 'details',
        '#title' => $category_name_map[$type],
        '#open' => TRUE,
      ];

      $form[$type . '_wrapper'][$type] = [
        '#type' => 'text_format',
        '#title' => $this->t('Start page content'),
        '#format' => $content['format'] ?? 'full_html',
        '#allowed_formats' => ['full_html'],
        '#default_value' => $content['value'] ?? NULL,
        '#required' => TRUE,
        '#description' => $description,
      ];
    }

    $this->moduleHandler->alter('default_start_page_content_config_form', $form, $form_state, $contents);
  }
}
