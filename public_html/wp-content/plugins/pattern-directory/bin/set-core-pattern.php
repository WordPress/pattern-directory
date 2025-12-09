<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * Mark submitted patterns to be distributed in core.
 *
 * This adds the expected post meta and terms, and updates the author to wordpressdotorg.
 *
 * To run locally, use `wp-env`, ex:
 * npm run wp-env run cli "php wp-content/plugins/pattern-directory/bin/set-core-pattern.php --post=<id> --block_types=core/header"
 *
 * To run in a sandbox, use php directly, ex:
 * php ./bin/set-core-pattern.php --post=<id> --block_types=core/header
 *
 * The `block_types` arg corresponds to the `blockTypes` in pattern registration,
 * used for suggestions on given block types. This is optional.
 */

namespace WordPressdotorg\Pattern_Directory;

// This script should only be called in a CLI environment.
if ( 'cli' !== php_sapi_name() ) {
	die();
}

$opts = getopt( '', array( 'post:', 'url:', 'abspath:', 'block_types:' ) );

if ( empty( $opts['url'] ) ) {
	$opts['url'] = 'https://wordpress.org/patterns/';
}

if ( empty( $opts['abspath'] ) && false !== strpos( __DIR__, 'wp-content' ) ) {
	$opts['abspath'] = substr( __DIR__, 0, strpos( __DIR__, 'wp-content' ) );
}

if ( ! empty( $opts['block_types'] ) ) {
	$opts['block_types'] = explode( ',', $opts['block_types'] );
} else {
	$opts['block_types'] = array();
}

// Bootstrap WordPress
$_SERVER['HTTP_HOST']   = parse_url( $opts['url'], PHP_URL_HOST );
$_SERVER['REQUEST_URI'] = parse_url( $opts['url'], PHP_URL_PATH );

require rtrim( $opts['abspath'], '/' ) . '/wp-load.php';

if ( ! isset( $opts['post'] ) ) {
	fwrite( STDERR, "Error! Specify a post ID with --post=<ID>\n" );
	die();
}

$pattern       = get_post( $opts['post'] );
$wporg_user_id = '5911429';

if ( $pattern ) {
	$pattern_id = $pattern->ID;

	// Update author
	$result = wp_update_post( array(
		'ID'          => $pattern_id,
		'post_author' => $wporg_user_id,
	) );
	if ( is_wp_error( $result ) ) {
		echo "Error updating author:\n";
		echo $result->get_error_message() . "\n";
	} else {
		echo "Updated author.\n";
	}

	// Add locale (just in case).
	$result = update_post_meta( $pattern_id, 'wpop_locale', 'en_US' );
	if ( is_wp_error( $result ) ) {
		echo "Error updating locale:\n";
		echo $result->get_error_message() . "\n";
	} else {
		echo "Updated locale.\n";
	}

	// Add `blockTypes` meta
	if ( count( $opts['block_types'] ) ) {
		delete_post_meta( $pattern_id, 'wpop_block_types' );

		foreach ( $opts['block_types'] as $block_type ) {
			$result = add_post_meta( $pattern_id, 'wpop_block_types', $block_type );
			if ( is_wp_error( $result ) ) {
				echo "Error updating block types:\n";
				echo $result->get_error_message() . "\n";
			} else {
				echo "Updated block types.\n";
			}
		}
	}

	// Add in WP version.
	$result = update_post_meta( $pattern_id, 'wpop_wp_version', '6.2' );
	if ( is_wp_error( $result ) ) {
		echo "Error updating version:\n";
		echo $result->get_error_message() . "\n";
	} else {
		echo "Updated version.\n";
	}

	// Add core tag
	$result = wp_set_post_terms( $pattern_id, 'core', 'wporg-pattern-keyword', false );
	if ( is_wp_error( $result ) ) {
		echo "Error updating post terms:\n";
		echo $result->get_error_message() . "\n";
	} else {
		echo "Marked for core.\n";
	}
} else {
	echo "Pattern not found.\n";
}
