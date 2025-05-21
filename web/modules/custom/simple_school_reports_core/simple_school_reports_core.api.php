<?php

use \Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Cache\CacheableMetadata;

/**
 * @file
 * Hooks provided by the simple_school_reports_core module.
 */

/**
 * Alter the step 2 of attendance report form.
 *
 * @param $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param array $context
 */
function hook_course_attendance_report_step_two_alter(&$form, FormStateInterface $form_state, array &$context) {
}

/**
 * @param $table
 * @param array $context
 */
function hook_invalid_absence_student_statistics_table_alter(&$table, array &$context) {
}

/**
 * @param array $default_templates
 */
function hook_default_message_templates_alter(array &$default_templates) {
}

/**
 * @param array $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param array $templates
 */
function hook_message_templates_config_form_alter(array &$form, FormStateInterface $form_state, array $templates) {
}

/**
 * @param array $default_content
 */
function hook_default_start_page_content_alter(array &$default_content) {
}

/**
 * @param array $form
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * @param array $contents
 */
function hook_default_start_page_content_config_form_alter(array &$form, FormStateInterface $form_state, array $contents) {
}

/**
 * @param array $local_actions
 * @param \Drupal\Core\Cache\CacheableMetadata $cache
 * @param array $context
 */
function hook_ssr_local_actions_alter(array &$local_actions, CacheableMetadata $cache, array $context) {
}

/**
 * @param \Drupal\user\UserInterface $user
 *
 * @return void
 */
function hook_ssr_login_access(\Drupal\user\UserInterface $user) {
}

/**
 * @param \Drupal\Core\Session\AccountInterface $account
 *
 * @return void
 */
function hook_ssr_start_page_route(\Drupal\Core\Session\AccountInterface $account) {
}
