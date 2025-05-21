<?php

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_dnp_support_deploy_10001() {
  $uids_without_dnp_username = \Drupal::entityTypeManager()->getStorage('user')->getQuery()
    ->accessCheck(FALSE)
    ->condition('field_dnp_username', NULL, 'IS NULL')
    ->execute();

  if (!empty($uids_without_dnp_username)) {
    $queue = \Drupal::service('queue')->get('modify_entity_queue');
    $queue->createQueue();
    foreach ($uids_without_dnp_username as $id) {
      $fields_to_modify = [
        'field_dnp_username' => NULL,
      ];
      $queue->createItem(['entity_type' => 'user', 'entity_id' => $id, 'fields' => $fields_to_modify]);
    }
  }
}

