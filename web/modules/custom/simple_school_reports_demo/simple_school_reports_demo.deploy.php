<?php

/**
 * Set to hide price info by default.
 */
function simple_school_reports_demo_deploy_10001() {
  \Drupal::state()->set('ssr_module_info.hide_price_info', TRUE);
}
