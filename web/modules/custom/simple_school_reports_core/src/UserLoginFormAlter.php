<?php

namespace Drupal\simple_school_reports_core;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class UserLoginFormAlter {

  public static function formAlter(&$form, FormStateInterface $form_state) {
    if (empty($form['#validate'])) {
      $form['#validate'] = [];
    }

    $form['#validate'] = array_merge([self::class, 'preValidateLoginMail'], $form['#validate']);
    $form['#validate'][] = [self::class, 'validateLoginAccess'];
    $form['#submit'][] = [self::class, 'handleRedirect'];
  }

  public static function preValidateLoginMail($form, FormStateInterface $form_state) {
    // Sanitize the mail.
    $mail = $form_state->getValue('name');
    $mail = trim($mail);
    $form_state->setValue('name', $mail);

    /** @var \Drupal\simple_school_reports_core\Service\EmailServiceInterface $email_service */
    $email_service = \Drupal::service('simple_school_reports_core.email_service');
    if ($email_service->skipMail($mail)) {
      $form_state->setError($form, t('The email %name is not valid.', ['%name' => $mail]));
    }
  }

  public static function validateLoginAccess($form, FormStateInterface $form_state) {
    if (empty($form_state->getErrors())) {
      $access = FALSE;
      if ($uid = $form_state->get('uid')) {
        $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
        /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
        $module_handler = \Drupal::service('module_handler');

        $access_resolvers = $module_handler->invokeAll('ssr_login_access', [$user]);
        /** @var \Drupal\Core\Access\AccessResult $access_resolver */
        foreach ($access_resolvers as $access_resolver) {
          if ($access_resolver->isAllowed()) {
            $access = TRUE;
          }
          if ($access_resolver->isForbidden()) {
            $access = FALSE;
            break;
          }
        }
      }

      if (!$access) {
        $form_state->setErrorByName('name', t('The username %name has not been activated or is blocked.', ['%name' => $form_state->getValue('name')]));
      }
    }
  }

  public static function handleRedirect($form, FormStateInterface $form_state) {
    if (\Drupal::currentUser()->isAuthenticated()) {
      $destination_url = NULL;
      try {
        $destination = \Drupal::request()->query->get('destination');
        $destination_url = $destination ? Url::fromUserInput($destination) : NULL;
        if (!$destination_url?->access()) {
          $destination_url = NULL;
        }
      }
      catch (\Exception $e) {
        $destination_url = NULL;
      }

      if ($destination_url) {
        $form_state->setRedirectUrl($destination_url);
        return;
      }

      \Drupal::request()->query->remove('destination');
      $form_state->setRedirect('simple_school_reports_core.start_page_resolver');
    }
  }
}
