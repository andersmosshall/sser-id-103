<?php

namespace Drupal\simple_school_reports_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;

/**
 * Controller for SanityCheckController.
 */
class SanityCheckController extends ControllerBase {

  public function sanityCheck() {
    $build = [];

    $build['settings_check_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Settings/config list'),
    ];

    $headers = [
      $this->t('Setting/Config'),
      $this->t('Value'),
    ];

    $rows = [];
    $rows[] = ['site_name', $this->config('system.site')->get('name')];
    $rows[] = ['site_mail', $this->config('system.site')->get('mail')];

    $value_safe_settings = [
      'ssr_bug_report_email',
      'ssr_school_name',
      'ssr_school_name_short',
      'ssr_school_organiser',
      'ssr_school_unit_code',
      'ssr_school_municipality',
      'ssr_school_municipality_code',
      'ssr_id',
    ];
    foreach ($value_safe_settings as $setting) {
      $rows[] = [$setting, Settings::get($setting)];
    }

    $secret_value_settings = [
      'ssr_abstract_hash_1',
      'ssr_abstract_hash_2',
      'ssr_abstract_hash_3',
      'ssr_abstract_hash_4',
      'ssr_abstract_hash_5',
      'ssr_abstract_hash_6',
      'ssr_abstract_hash_7',
      'ssr_abstract_hash_8',
      'ssr_abstract_hash_9',
      'ssr_common_salt',
    ];
    foreach ($secret_value_settings as $setting) {
      $rows[] = [$setting, !!Settings::get($setting) ? 'OK' : 'NOT SET'];
    }

    $build['settings_check_table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    ];

    // Logo check.
    $build['logos_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Logos'),
    ];

    /** @var \Drupal\simple_school_reports_core\Service\FileTemplateServiceInterface $file_template_service */
    $file_template_service = \Drupal::service('simple_school_reports_core.file_template_service');
    $templates_base_path = DIRECTORY_SEPARATOR . $this->moduleHandler()->getModule('simple_school_reports_core')->getPath() . DIRECTORY_SEPARATOR . 'ssr-file-templates' . DIRECTORY_SEPARATOR;
    // Header logo.
    $build['header_logo_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Header logo'),
    ];
    $header_logo = $file_template_service->getFileTemplate('logo_header');
    if ($header_logo) {
      $build['header_logo'] = [
        '#theme' => 'image',
        '#uri' => $header_logo->getFileUri(),
        '#alt' => $this->t('Header logo'),
        '#title' => $this->t('Header logo'),
        '#attributes' => [
          'style' => 'height: 90px; width: auto;',
        ],
      ];
    }
    else {
      $build['header_logo'] = [
        '#markup' => $this->t('No header logo found.'),
      ];
    }

    // Document logo left.
    $build['doc_logo_l'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Document logo left'),
    ];
    $doc_logo_left = $file_template_service->getFileTemplate('doc_logo_left')?->getFileUri();
    if (!$doc_logo_left) {
      $doc_logo_left = $templates_base_path . 'logo_example_l.jpeg';
    }
    $build['doc_logo_left'] = [
      '#theme' => 'image',
      '#uri' => $doc_logo_left,
      '#alt' => $this->t('Document logo left'),
      '#title' => $this->t('Document logo left'),
      '#attributes' => [
        'style' => 'height: 90px; width: auto;',
      ],
    ];

    // Document logo center.
    $build['doc_logo_c'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Document logo center'),
    ];
    $doc_logo_center = $file_template_service->getFileTemplate('doc_logo_center')?->getFileUri();
    if (!$doc_logo_center) {
      $doc_logo_center = $templates_base_path . 'logo_example_c.jpeg';
    }
    $build['doc_logo_center'] = [
      '#theme' => 'image',
      '#uri' => $doc_logo_center,
      '#alt' => $this->t('Document logo center'),
      '#title' => $this->t('Document logo center'),
      '#attributes' => [
        'style' => 'height: 90px; width: auto;',
      ],
    ];

    // Document logo right.
    $build['doc_logo_r'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Document logo right'),
    ];
    $doc_logo_right = $file_template_service->getFileTemplate('doc_logo_right')?->getFileUri();
    if (!$doc_logo_right) {
      $doc_logo_right = $templates_base_path . 'logo_example_r.jpeg';
    }
    $build['doc_logo_right'] = [
      '#theme' => 'image',
      '#uri' => $doc_logo_right,
      '#alt' => $this->t('Document logo right'),
      '#title' => $this->t('Document logo right'),
      '#attributes' => [
        'style' => 'height: 90px; width: auto;',
      ],
    ];


    $build['modules_list_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Modules enabled'),
    ];

    $headers = [
      $this->t('Module'),
      $this->t('Enabled'),
    ];

    $modules = simple_school_reports_module_info_get_modules();
    $rows = [];

    foreach ($modules as $module => $name) {
      $rows[] = [$name, $this->moduleHandler()->moduleExists($module) ? $this->t('Yes') : $this->t('No')];
    }

    $build['modules_list_table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    ];

    return $build;
  }

}
