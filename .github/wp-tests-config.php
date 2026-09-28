<?php
/**
 * WordPress test-suite configuration for CI.
 *
 * Used by the unit-tests workflow, which runs PHPUnit directly on the runner
 * against a downloaded WordPress core build and the runner's MySQL service.
 * Locations come from the environment so the workflow stays the single source
 * of truth for paths.
 *
 * @package pattern-directory
 */

declare( strict_types = 1 );

define( 'ABSPATH', rtrim( (string) getenv( 'WP_CORE_DIR' ), '/' ) . '/' );

define( 'DB_NAME', (string) ( getenv( 'WP_DB_NAME' ) ?: 'wordpress_test' ) );
define( 'DB_USER', (string) ( getenv( 'WP_DB_USER' ) ?: 'root' ) );
define( 'DB_PASSWORD', (string) ( getenv( 'WP_DB_PASSWORD' ) ?: 'root' ) );
define( 'DB_HOST', (string) ( getenv( 'WP_DB_HOST' ) ?: '127.0.0.1' ) );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );
define( 'WP_DEBUG', true );
