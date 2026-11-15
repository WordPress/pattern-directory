<?php
/**
 * Stand-in for the wporg-internal-notes plugin, which CI doesn't load.
 *
 * Records notes in memory instead of saving them, so other tests are unaffected.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\InternalNotes;

if ( ! function_exists( __NAMESPACE__ . '\create_note' ) ) {
	if ( ! defined( __NAMESPACE__ . '\LOG_POST_TYPE' ) ) {
		define( __NAMESPACE__ . '\LOG_POST_TYPE', 'wporg-log-note' );
	}

	$GLOBALS['pattern_directory_test_notes'] = array();

	/**
	 * Record a note for a post.
	 *
	 * @param int   $post_id The post the note belongs to.
	 * @param array $data    The note data.
	 *
	 * @return void
	 */
	function create_note( int $post_id, array $data ): void {
		$GLOBALS['pattern_directory_test_notes'][] = array_merge( $data, array( 'post_parent' => $post_id ) );
	}
}
