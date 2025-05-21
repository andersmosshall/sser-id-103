<?php

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_iup_deploy_9001() {
  /** @var \Drupal\node\NodeStorage $node_storage */
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  $nids = $node_storage->getQuery()
    ->condition('type', 'iup')
    ->condition('field_state', 'done')
    ->accessCheck(FALSE)
    ->execute();

  foreach ($nids as $nid) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->load($nid);

    if (!$node->get('field_document_date')->isEmpty()) {
      continue;
    }

    /** @var \Drupal\node\NodeInterface $iup_round_node */
    $iup_round_node = current($node->get('field_iup_round')->referencedEntities());

    $document_date = (new \DateTime('2022-04-01'))->setTime(0,0,0)->getTimestamp();

    if ($iup_round_node->hasField('field_document_date') && !$iup_round_node->get('field_document_date')->isEmpty()) {
      $document_date = $iup_round_node->get('field_document_date')->value;
    }

    $node->set('field_document_date', $document_date);
    $node->setNewRevision(FALSE);
    $node->save();
  }
}
