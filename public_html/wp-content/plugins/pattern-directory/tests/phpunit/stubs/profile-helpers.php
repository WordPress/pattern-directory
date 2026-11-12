<?php
/**
 * Test stand-in for the WordPress.org profiles API.
 *
 * The real `add_activity()` lives in the meta repository's `pub` mu-plugin, which the test environment does not
 * load. This one records each call so tests can check what a pattern sends. When the real helper is loaded first,
 * this definition is skipped and the tests that rely on it skip themselves.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Profiles;

if ( ! function_exists( __NAMESPACE__ . '\add_activity' ) ) {
	/**
	 * Record a call and return the result the current test asks for.
	 *
	 * @param string     $component Activity component.
	 * @param string     $type      Activity type.
	 * @param int|string $user      User ID, login or slug.
	 * @param array      $args      Activity arguments.
	 *
	 * @return bool
	 */
	function add_activity( string $component, string $type, $user, array $args ) {
		$GLOBALS['wporg_profile_activity_calls'][] = array( $component, $type, $user, $args );

		return $GLOBALS['wporg_profile_activity_result'] ?? true;
	}

	$GLOBALS['wporg_profile_activity_stub'] = true;
}
