<?php

/**
 * State caching.
 *
 * State caching uses the cache collector pattern to cache all requested keys
 * from the state API in a single cache entry, which can greatly reduce the
 * amount of database queries. However, some sites may use state with a
 * lot of dynamic keys which could result in a very large cache.
 */
$settings['state_cache'] = TRUE;

// Hide error reporting.
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
ini_set('display_errors', FALSE);
ini_set('display_startup_errors', FALSE);

if (file_exists($app_root . '/' . $site_path . '/settings.common-local.php')) {
  include $app_root . '/' . $site_path . '/settings.common-local.php';
}

// Only local!!
//$databases['default']['default'] = array (
//  'database' => 'drupal9',
//  'username' => 'drupal9',
//  'password' => 'drupal9',
//  'prefix' => 'ss103_',
//  'host' => 'database',
//  'port' => '3306',
//  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
//  'driver' => 'mysql',
//  'charset' => 'utf8mb4',
//  'collation' => 'utf8mb4_swedish_ci',
//  'init_commands' => [
//    'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
//  ],
//);

$settings['trusted_host_patterns'] = array(
  '^ssr-sserdemo1.loc$',
  '^ssr.loc$',
  '^ssr-debug.loc$',
  '^sser-sserdemo1.loc$',
  '^sser.loc$',
  '^sser-debug.loc$',
  '^www.sserdemo1.simpleschoolreports.se$',
  '^sserdemo1.simpleschoolreports.se$',
);

// Force HTTPS
// PHP_SAPI command line (cli) check prevents drush commands from giving a
// "Drush command terminated abnormally due to an unrecoverable error"
if ( (!array_key_exists('HTTPS', $_SERVER)) && (PHP_SAPI !== 'cli') ) {
  header('HTTP/1.1 301 Moved Permanently');
  header('Location: https://www.sserdemo1.simpleschoolreports.se'. $_SERVER['REQUEST_URI']);
  exit();
}

$config['config_split.config_split.local']['status'] = TRUE;

$settings['ssr_school_name'] = 'SSER Demo1';
$settings['ssr_school_name_short'] = 'DEMO';
$settings['ssr_school_organiser'] = 'Selektiv System AB';
$settings['ssr_school_unit_code'] = '12345678';
$settings['ssr_school_municipality'] = 'Teststad';
$settings['ssr_school_municipality_code'] = '1234';
$settings['ssr_id'] = 103;

$settings['ssr_grade_from'] = 0;
$settings['ssr_grade_to'] = 9;

$settings['ssr_bug_report_email'] = 'ronnie.afzelius@selektiv.se';
$settings['ssr_no_reply_email'] = 'sser@selektiv.se';

// Number of extra super admins (user 1 not included). 0 = only user 1 is super
// admin.
$settings['ssr_allowed_super_admins'] = 2;

// Limit to put site in maintenance mode due to suspicious mail activity, if the
// mail count is above this number.
$settings['ssr_suspicious_mail_count'] = 6000;

// Fallback toolbar color #0f0f0f.
$settings['ssr_toolbar_color'] = '#0f0f0f';
