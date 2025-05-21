<?php

/**
 * Add iup_goal prefill options to all iup_goals.
 */
function simple_school_reports_extension_proxy_deploy_10001() {
  $iup_round_nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'iup_round')
    ->execute();

  if (empty($iup_round_nids)) {
    return;
  }

  $iup_rounds = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($iup_round_nids);

  /** @var \Drupal\node\NodeInterface $iup_round */
  foreach ($iup_rounds as $iup_round) {
    $iup_round->set('field_prefill', ['iup_goal']);
    $iup_round->setSyncing(TRUE);
    $iup_round->save();
  }
}
