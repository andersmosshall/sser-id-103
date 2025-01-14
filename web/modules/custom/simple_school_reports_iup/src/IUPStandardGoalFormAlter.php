<?php

namespace Drupal\simple_school_reports_iup;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class IUPStandardGoalFormAlter {

  public static function formAlter(&$form, FormStateInterface $form_state) {
    $form['status']['#access'] = FALSE;
    $form['relations']['#access'] = FALSE;
    $form['status']['widget']['value']['#default_value'] = TRUE;

    $form['#after_build'][] = [self::class, 'afterBuildAlter'];
  }

  public static function afterBuildAlter($form, FormStateInterface $form_state) {
    $description = '';

    /** @var ReplaceTokenServiceInterface $token_service */
    $token_service = \Drupal::service('simple_school_reports_core.replace_token_service');

    $replace_tokens = $token_service->getReplaceTokenDescriptions([
      ReplaceTokenServiceInterface::STUDENT_REPLACE_TOKENS,
    ], TRUE);

    if (!empty($replace_tokens)) {
      $description_lines = ['<b>' . t('Replacement patterns') . ':</b>'];
      foreach ($replace_tokens as $token => $description) {
        $description_lines[] = $token . ' = ' . $description;
      }
      $description = implode('<br>', $description_lines);
    }


    $form['field_iup_goal']['widget'][0]['value']['#description'] = $description;
    return $form;
  }
}
