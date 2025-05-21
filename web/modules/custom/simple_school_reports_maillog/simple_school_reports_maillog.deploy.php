<?php

/**
 * Implements hook_deploy_NAME().
 */
function simple_school_reports_maillog_deploy_10001() {
  $maillog_storage = \Drupal::entityTypeManager()->getStorage('ssr_maillog');
  $ids = $maillog_storage->getQuery()->accessCheck(FALSE)->execute();

  $queue = \Drupal::service('queue')->get('modify_entity_queue');
  $queue->createQueue();
  foreach ($ids as $id) {
    $fields_to_modify = [
      'status' => TRUE,
    ];
    $queue->createItem(['entity_type' => 'ssr_maillog', 'entity_id' => $id, 'fields' => $fields_to_modify]);
  }
}
