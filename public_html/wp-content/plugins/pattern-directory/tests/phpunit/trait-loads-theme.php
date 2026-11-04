<?php
/**
 * Load the theme's functions for tests.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

/**
 * Loads the theme's functions without leaving its hooks registered.
 */
trait Loads_Theme {
	/**
	 * Load the theme's functions.php, then drop the hooks it registered.
	 *
	 * Left in place, its `pre_get_posts` hook would force `curation=core` on later tests' queries, so tests add
	 * the theme hooks they need themselves.
	 */
	public static function load_theme(): void {
		if ( function_exists( '\WordPressdotorg\Theme\Pattern_Directory_2024\set_document_title' ) ) {
			return;
		}

		// `$wp_filter` holds `WP_Hook` objects, so the array has to be cloned, not just copied.
		$hooks_before_theme = array_map(
			function ( \WP_Hook $hook ): \WP_Hook {
				return clone $hook;
			},
			$GLOBALS['wp_filter']
		);

		require_once dirname( __DIR__, 4 ) . '/themes/wporg-pattern-directory-2024/functions.php';

		$GLOBALS['wp_filter'] = $hooks_before_theme; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the snapshot taken above.
	}
}
