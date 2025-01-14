<?php

namespace Drupal\simple_school_reports_iup;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class IUPGoalsFormAlter {

  public static function exposedViewsFormAlter(&$form, FormStateInterface $form_state) {
    $iup_round_options = [
      0 => t('none'),
    ];

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $iup_round_nids = array_values($node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'iup_round')
      ->sort('nid', 'DESC')
      ->execute());

    if (!empty($iup_round_nids)) {
      $iup_round_nodes = $node_storage->loadMultiple($iup_round_nids);
      $first = TRUE;
      foreach ($iup_round_nodes as $iup_round_node) {
        if ($first) {
          $iup_round_options[0] = $iup_round_node->label();
          $first = FALSE;
          continue;
        }
        $iup_round_options[$iup_round_node->id()] = $iup_round_node->label();
      }
    }

    $teacher_options = [
      '' => t('Any'),
    ];

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $uids = array_values($user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', ['teacher'], 'IN')
      ->sort('field_first_name')
      ->sort('field_last_name')
      ->execute());

    if (!empty($uids)) {
      $users = $user_storage->loadMultiple($uids);
      /** @var \Drupal\user\UserInterface $user */
      foreach ($users as $user) {
        $teacher_options[$user->id()] = $user->getDisplayName();
      }
    }

    if (!empty($form['field_iup_round_target_id'])) {
      $form['field_iup_round_target_id']['#type'] = 'select';
      $form['field_iup_round_target_id']['#options'] = $iup_round_options;
      unset($form['field_iup_round_target_id']['#size']);
    }

    if (!empty($form['field_teacher_target_id'])) {
      $form['field_teacher_target_id']['#type'] = 'select';
      $form['field_teacher_target_id']['#options'] = $teacher_options;
      unset($form['field_teacher_target_id']['#size']);
    }

    if (!empty($form['field_state_value_1']['#options'])) {
      $form['field_state_value_1']['#options']['done'] = t('Yes');
      $form['field_state_value_1']['#options']['started'] = t('No');
    }
  }

  public static function getFormEntity(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function formAlter(&$form, FormStateInterface $form_state) {
    $node = self::getFormEntity($form_state);

    if ($node->bundle() === 'iup_goal') {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $form['preview_iup_goal'] = [
        '#type'   => 'container',
        '#weight' => -10,
      ];
      $form['preview_iup_goal']['build'] = $view_builder->view($node);
    }
  }
}
