<?php

/**
 * Set to hide price info by default.
 */
function sser_base_deploy_10001() {
  \Drupal::state()->set('ssr_module_info.hide_price_info', TRUE);
}
