<?php

if (file_exists($app_root . '/' . $site_path . '/settings.common-local.php')) {
  include $app_root . '/' . $site_path . '/settings.common-local.php';
}

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

$databases['default']['default'] = array (
  'database' => 'drupal9',
  'username' => 'drupal9',
  'password' => 'drupal9',
  'prefix' => 'msr_d1_',
  'host' => 'database',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
  'charset' => 'utf8mb4',
  'collation' => 'utf8mb4_swedish_ci',
  'init_commands' => [
    'isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED',
  ],
);

$settings['trusted_host_patterns'] = array(
  '^ssr.loc$',
  '^ssr-debug.loc$',
);

// Force HTTPS
// PHP_SAPI command line (cli) check prevents drush commands from giving a
// "Drush command terminated abnormally due to an unrecoverable error"
if ( (!array_key_exists('HTTPS', $_SERVER)) && (PHP_SAPI !== 'cli') ) {
  header('HTTP/1.1 301 Moved Permanently');
  header('Location: https://ssr.loc'. $_SERVER['REQUEST_URI']);
  exit();
}

/**
 * Logging.
 */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
$config['system.logging']['error_level'] = 'verbose';

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
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

$config['config_split.config_split.local']['status'] = TRUE;

/**
 * State caching.
 *
 * State caching uses the cache collector pattern to cache all requested keys
 * from the state API in a single cache entry, which can greatly reduce the
 * amount of database queries. However, some sites may use state with a
 * lot of dynamic keys which could result in a very large cache.
 */
$settings['state_cache'] = TRUE;

$settings['ssr_school_name'] = 'Demoskolan';
$settings['ssr_school_name_short'] = 'Dem';
$settings['ssr_school_organiser'] = 'Demoskolan AB';
$settings['ssr_school_unit_code'] = '12345678';
$settings['ssr_school_municipality'] = 'Örnsköldsvik';
$settings['ssr_school_municipality_code'] = '2284';
$settings['ssr_id'] = 0;

$settings['ssr_max_grade_student_group_size'] = 32;

$settings['ssr_grade_from'] = 0;
$settings['ssr_grade_to'] = 9;

$settings['ssr_max_written_reviews_subject_list'] = 18;

$settings['ssr_toolbar_color'] = '#3a5ebd';

$settings['ssr_bug_report_email'] = 'anders@mosshall.se';
$settings['ssr_no_reply_email'] = 'anders@mosshall.se';
