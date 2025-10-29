<?php

namespace Drupal\simple_school_reports_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;

/**
 * Controller for SanityCheckController.
 */
class SanityCheckController extends ControllerBase {

  protected function getConfigValue(array $config_path): string | null {
    if (count($config_path) < 2) {
      return NULL;
    }

    // First item is the config file.
    $config_name = array_shift($config_path);
    $config_object = $this->config($config_name);

    // Second item is the config key.
    $config_key = array_shift($config_path);
    $config_value = $config_object->get($config_key);

    // Loop through the rest of the config path.
    foreach ($config_path as $key) {
      if (is_array($config_value) && array_key_exists($key, $config_value)) {
        $config_value = $config_value[$key];
      }
      else {
        return NULL;
      }
    }

    if (is_array($config_value)) {
      return implode(', ', $config_value);
    }

    return (string) $config_value;
  }

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

    $safe_config_paths = [
      ['symfony_mailer.settings', 'default_transport'],
      ['symfony_mailer.mailer_transport.ssr_smtp', 'configuration', 'host'],
      ['symfony_mailer.mailer_transport.ssr_smtp', 'configuration', 'port'],
      ['symfony_mailer.mailer_transport.ssr_smtp', 'configuration', 'user'],
    ];
    foreach ($safe_config_paths as $config_paths) {
      $value = $this->getConfigValue($config_paths);
      $rows[] = [implode('.', $config_paths), $value ?? 'NOT SET'];
    }

    $secret_config_paths = [
      ['symfony_mailer.mailer_transport.ssr_smtp', 'configuration', 'pass'],
    ];
    foreach ($secret_config_paths as $config_paths) {
      $value = $this->getConfigValue($config_paths);
      $rows[] = [implode('.', $config_paths), $value === 'SECRET' ? 'NOT SET' : 'OK'];
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
      $build['doc_logo_left_warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No document logo found. THIS IS AN EXAMPLE OF A DOC LOGO:'),
        '#attributes' => [
          'class' => ['warning'],
        ],
      ];
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
      $build['doc_logo_center_warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No document logo found. THIS IS AN EXAMPLE OF A DOC LOGO:'),
        '#attributes' => [
          'class' => ['warning'],
        ],
      ];
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
      $build['doc_logo_right_warning'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('No document logo found. THIS IS AN EXAMPLE OF A DOC LOGO:'),
        '#attributes' => [
          'class' => ['warning'],
        ],
      ];
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

    $build['organization_list_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Organization/School'),
    ];

    $organization_storage = $this->entityTypeManager()->getStorage('ssr_organization');
    $organization_ids = $organization_storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('sort_index', 'ASC')
      ->sort('label', 'ASC')
      ->execute();

    if (!empty($organization_ids)) {
      $organizations = $organization_storage->loadMultiple($organization_ids);
      foreach ($organizations as $organization) {
        $view_builder = $this->entityTypeManager->getViewBuilder('ssr_organization');
        $build['organization_label_' . $organization->id()] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $organization->label(),
        ];
        $build['organization_' . $organization->id()] = $view_builder->view($organization);
        $build['organization_devider_' . $organization->id()] = [
          '#type' => 'html_tag',
          '#tag' => 'hr',
        ];
      }
    }


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
