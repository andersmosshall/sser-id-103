<?php

namespace Drupal\simple_school_reports_extension_proxy;

use Drupal\Core\Form\FormStateInterface;

class ViewsFormAlter {


  public static function userFormAlter(&$form, FormStateInterface $form_state, string $form_id) {
    if (!empty($form['header']['user_bulk_form']['action']['#options'])) {
      $action_module_map = self::getActionModuleMap();
      $options = &$form['header']['user_bulk_form']['action']['#options'];
      foreach ($options as $action_id => $label) {
        $module_constraints = $action_module_map[$action_id] ?? [];
        if (empty($module_constraints)) {
          continue;
        }

        $has_any_module = FALSE;
        foreach ($module_constraints as $module) {
          if (\Drupal::moduleHandler()->moduleExists($module)) {
            $has_any_module = TRUE;
            break;
          }
        }
        if (!$has_any_module) {
          unset($options[$action_id]);
        }
      }
    }

  }

  public static function getActionModuleMap(): array {
    return [
      'ssr_assign_to_class' => [
        'simple_school_reports_class',
      ],
      'ssr_export_users' => [
        'simple_school_reports_pmo_export',
      ],
      'extension_proxy_consent_reminders' => [
        'simple_school_reports_consents',
      ],
      'simple_school_reports_examinations_support_set_examination_results' => [
        'simple_school_reports_examinations',
      ],
    ];
  }
}
