<?php

if (file_exists($app_root . '/' . $site_path . '/settings.common-secrets.php')) {
  include $app_root . '/' . $site_path . '/settings.common-secrets.php';
}

if (file_exists($app_root . '/' . $site_path . '/settings.local-secrets.php')) {
  include $app_root . '/' . $site_path . '/settings.local-secrets.php';
}

$settings['ssr_catalog_id'] = [
  'BL' => 1,
  'BI' => 2,
  'EN' => 3,
  'FY' => 4,
  'GE' => 5,
  'HI' => 7,
  'HKK' => 6,
  'IDH' => 8,
  'KE' => 9,
  'ML' => 14,
  'ML_COM' => 13,
  'M1' => 12,
  'M1_COM' => 11,
  'M2' => 12,
  'M2_COM' => 11,
  'MA' => 10,
  'MU' => 15,
  'NO' => 25,
  'RE' => 16,
  'SH' => 17,
  'SL' => 18,
  'SO' => 26,
  'SV' => 19,
  'SVA' => 20,
  'TK' => 21,
  'TN' => 27,
];

$settings['ssr_excluded_catalog_label'] = [
  'ML' => '2',
  'M1' => '2',
  'M2' => '2',
  'SV' => '2',
  'SVA' => '2',
  'TN' => '2',
  'ML_COM' => '2',
  'M1_COM' => '2',
  'M2_COM' => '2',
];
