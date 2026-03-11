<?php

namespace Drupal\simple_school_reports_core\Traits;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Helper to keep track of double posts in forms that some browser seems to do.
 *
 * This is a workaround as we have not narrowed down the root cause of the
 * double posts.
 */
trait PreventDoublePostTrait {

  const DOUBLE_POST_THROTTLE =  5;

  use StringTranslationTrait;

  protected function getSession() {
    return \Drupal::service('session');
  }

  private function hashFormData(FormStateInterface $form_state): string {
    $form_data = $form_state->getValues();

    unset($form_data['form_build_id']);
    unset($form_data['form_token']);

    // Append current path to further narrow down the double post detection.
    $path = \Drupal::request()->getPathInfo();

    return hash('sha256', JSON::encode($form_data) . $path);
  }

  private function getFormDataToLog(FormStateInterface $form_state): string {
    $form_data = $form_state->getValues();

    unset($form_data['pass']);
    unset($form_data['password']);
    unset($form_data['pass1']);
    unset($form_data['pass2']);
    unset($form_data['field_ssn']);

    $form_data['user_agent'] = \Drupal::request()->server->get('HTTP_USER_AGENT', 'No user agent');

    $message = gzcompress(JSON::encode($form_data), 9);
    $message = base64_encode($message);

    return 'ssr-compressed-log--' . $message;
  }

  protected function isDoupblePost(FormStateInterface $form_state): bool {
    $form_id = $form_state->getFormObject()->getFormId();
    $session_data = $this->getSession()->get('ssr_double_post_lookup_' . $form_id);
    if (!is_array($session_data) || empty($session_data['data_hash']) || empty($session_data['timestamp'])) {
      return false;
    }

    if (\Drupal::time()->getCurrentTime() - $session_data['timestamp'] > self::DOUBLE_POST_THROTTLE) {
      return false;
    }

    return $session_data['data_hash'] === $this->hashFormData($form_state);
  }

  protected function skipValidation(FormStateInterface $form_state): bool {
    return $this->isDoupblePost($form_state);
  }

  protected function earlyReturnSubmit(FormStateInterface $form_state, bool $add_status_message = TRUE): bool {
    if (!$this->isDoupblePost($form_state)) {
      return false;
    }

    $form_id = $form_state->getFormObject()->getFormId();
    try {
      \Drupal::logger('ssr_double_post')->warning('Double post detected and bypassed: ' . $form_id . ' ' . $this->getFormDataToLog($form_state));
    }
    catch (\Exception $e) {
      \Drupal::logger('ssr_double_post')->error('Double post detected and bypassed: ' . $form_id . ' but failed to log form data');
    }

    if ($add_status_message) {
      $data_to_session = $this->getSession()->get('ssr_double_post_lookup_' . $form_id);
      $message = is_array($data_to_session) && !empty($data_to_session['status_message']) ? $data_to_session['status_message'] : $this->t('Done');
      \Drupal::messenger()->addStatus($message);
    }

    return true;
  }

  protected function registerSubmit(FormStateInterface $form_state, null|string|TranslatableMarkup $status_message = NULL): void {
    \Drupal::time()->getCurrentTime();
    if ($status_message) {
      \Drupal::messenger()->addStatus($status_message);
    }

    $form_id = $form_state->getFormObject()->getFormId();
    $timestamp = \Drupal::time()->getCurrentTime();
    $data_to_session = [
      'form_id' => $form_id,
      'data_hash' => $this->hashFormData($form_state),
      'timestamp' => $timestamp,
      'status_message' => $status_message,
    ];
    $this->getSession()->set('ssr_double_post_lookup_' . $form_id, $data_to_session);
  }

}
