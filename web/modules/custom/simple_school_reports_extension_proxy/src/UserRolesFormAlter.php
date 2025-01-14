<?php

namespace Drupal\simple_school_reports_extension_proxy;

use Drupal\Core\Form\FormStateInterface;

class UserRolesFormAlter {

  public static function userFormAlter(&$form, FormStateInterface $form_state) {
    if (!empty($form['account']['roles'])) {
      $field_access = array_key_exists('#access', $form['account']['roles']) ? $form['account']['roles']['#access'] : TRUE;
      if ($field_access) {
        /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
        $module_handler = \Drupal::service('module_handler');
        // Unset budget roles.
        if (!$module_handler->moduleExists('simple_school_reports_budget')) {
          unset($form['account']['roles']['#options']['budget_administrator']);
          unset($form['account']['roles']['#options']['budget_reviewer']);
        }
      }
    }

  }
}
