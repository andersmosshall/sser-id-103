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
  'HKK' => 6,
  'HI' => 7,
  'IDH' => 8,
  'KE' => 9,
  'MA' => 10,
  // 'Moderna språk, elevens val',
  'M1_COM' => 11,
  'M1' => 12,
  // 'Moderna språk, språkval',
  'M2_COM' => 13,
  'M2' => 14,
  // Modersmål.
  'ML_COM' => 15,
  'ML' => 16,
  'MU' => 17,
  'RE' => 18,
  'SH' => 19,
  'SL' => 20,
  'SV' => 21,
  'SVA' => 22,
  'TK' => 23,

  // Outside catalog for now.
  'NO' => 24,
  'SO' => 25,
  'TN' => 26,
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

$settings['ssr_max_grade_student_group_size'] = 32;
