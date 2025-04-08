<?php

/**
 * Location of the site configuration files.
 *
 * The $settings['config_sync_directory'] specifies the location of file system
 * directory used for syncing configuration data. On install, the directory is
 * created. This is used for configuration imports.
 *
 * The default location for this directory is inside a randomly-named
 * directory in the public files path. The setting below allows you to set
 * its location.
 */
$settings['config_sync_directory'] = '../config/sync';

/**
 * Private file path:
 *
 * A local file system path where private files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * Note: Caches need to be cleared when this value is changed to make the
 * private:// stream wrapper available to the system.
 *
 * See https://www.drupal.org/documentation/modules/file for more information
 * about securing private files.
 */
$settings['file_private_path'] = 'sites/default/private-files';

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
  'TN' => 23,
  'TK' => 24,

  // Not supported anymore as a grade subject.
//  'NO' => 24,
//  'SO' => 25,
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

$settings['ssr_max_written_reviews_subject_list'] = 18;
