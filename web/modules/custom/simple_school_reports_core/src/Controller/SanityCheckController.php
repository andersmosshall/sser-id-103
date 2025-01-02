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
      'ssr_school_organiser',
      'ssr_school_unit_code',
      'ssr_school_municipality',
    ];
    foreach ($value_safe_settings as $setting) {
      $rows[] = [$setting, Settings::get($setting)];
    }

    $secret_value_settings = [
      'ssr_examination_result_not_applicable',
      'ssr_examination_result_not_completed',
      'ssr_examination_result_completed',
      'ssr_examination_result_failed',
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

    // ToDo check files...

    return $build;
  }

}
