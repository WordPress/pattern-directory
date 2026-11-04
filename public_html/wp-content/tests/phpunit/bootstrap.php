<?php
/**
 * PHPUnit bootstrap file
 */

// Require composer dependencies: wp-env maps the repo's vendor dir to /var/www/html; on a plain host checkout it lives at the repo root.
$_autoload_candidates = array(
	'/var/www/html/vendor/autoload.php',
	dirname( __DIR__, 4 ) . '/vendor/autoload.php',
);
foreach ( $_autoload_candidates as $_autoload ) {
	if ( file_exists( $_autoload ) ) {
		require_once $_autoload;
		break;
	}
}

// If we're running in WP's build directory, ensure that WP knows that, too.
if ( 'build' === getenv( 'LOCAL_DIR' ) ) {
	define( 'WP_RUN_CORE_TESTS', true );
}

// Determine the tests directory (from a WP dev checkout).
// Try the WP_TESTS_DIR environment variable first.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

// Next, try the WP_PHPUNIT composer package.
if ( ! $_tests_dir ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}

// See if we're installed inside an existing WP dev instance.
if ( ! $_tests_dir ) {
	$_try_tests_dir = __DIR__ . '/../../../../../tests/phpunit';
	if ( file_exists( $_try_tests_dir . '/includes/functions.php' ) ) {
		$_tests_dir = $_try_tests_dir;
	}
}
// Fallback.
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// The test library reads the config path from a constant; bridge the env var CI sets (wp-phpunit does the same).
if ( getenv( 'WP_TESTS_CONFIG_FILE_PATH' ) && ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	define( 'WP_TESTS_CONFIG_FILE_PATH', getenv( 'WP_TESTS_CONFIG_FILE_PATH' ) );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugins() {
	// The locale stand-in loads as an mu-plugin under wp-env; on a plain host checkout load it directly (its definitions are function_exists-guarded).
	$_locales = dirname( __DIR__, 4 ) . '/.wp-env/wporg-locales.php';
	if ( file_exists( $_locales ) ) {
		require_once $_locales;
	}

	require dirname( dirname( __DIR__ ) ) . '/plugins/pattern-directory/bootstrap.php';
	require dirname( dirname( __DIR__ ) ) . '/plugins/pattern-creator/pattern-creator.php';
	require dirname( dirname( __DIR__ ) ) . '/plugins/pattern-translations/pattern-translations.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugins' );

/**
 * Adds a wp_die handler for use during tests.
 *
 * If bootstrap.php triggers wp_die, it will not cause the script to fail. This
 * means that tests will look like they passed even though they should have
 * failed. So we throw an exception if WordPress dies during test setup. This
 * way the failure is observable.
 *
 * @param string|WP_Error $message The error message.
 *
 * @throws Exception When a `wp_die()` occurs.
 */
function fail_if_died( $message ) {
	if ( is_wp_error( $message ) ) {
		$message = $message->get_error_message();
	}

	throw new Exception( 'WordPress died: ' . $message );
}
tests_add_filter( 'wp_die_handler', 'fail_if_died' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Use existing behavior for wp_die during actual test execution.
remove_filter( 'wp_die_handler', 'fail_if_died' );
