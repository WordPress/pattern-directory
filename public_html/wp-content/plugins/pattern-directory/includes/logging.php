<?php
/**
 * Logging for pattern status changes.
 *
 * @package WordPressdotorg\Pattern_Directory
 */

namespace WordPressdotorg\Pattern_Directory\Logging;

use WordPressdotorg\InternalNotes;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN_POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ PENDING_STATUS, RESOLVED_STATUS, POST_TYPE as FLAG_POST_TYPE };

/**
 * Actions and filters.
 */
add_action( 'wp_after_insert_post', __NAMESPACE__ . '\flag_status_change', 5, 4 );

/**
 * Check if logging is enabled for patterns.
 *
 * @return bool
 */
function logging_enabled() {
	$support       = post_type_supports( PATTERN_POST_TYPE, 'wporg-internal-notes' )
					&& post_type_supports( PATTERN_POST_TYPE, 'wporg-log-notes' );
	$callable_func = is_callable( '\\WordPressdotorg\\InternalNotes\\create_note' );

	return $support && $callable_func;
}

/**
 * Add a log entry to a pattern when a flag's status changes.
 *
 * Runs once the flag is fully saved (terms and meta included), and before
 * `check_flag_threshold()` so the new flag is logged ahead of any status
 * change it causes on the pattern.
 *
 * @param int           $post_id     Post ID.
 * @param \WP_Post      $post        Post object.
 * @param bool          $update      Whether this is an existing post being updated.
 * @param null|\WP_Post $post_before Null for new posts, the WP_Post object prior
 *                                   to the update for updated posts.
 *
 * @return void
 */
function flag_status_change( $post_id, $post, $update, $post_before ) {
	if ( ! logging_enabled() ) {
		return;
	}

	if ( FLAG_POST_TYPE !== get_post_type( $post ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$pattern_id = $post->post_parent;

	if ( ! $pattern_id ) {
		return;
	}

	$new_status = $post->post_status;
	$old_status = $post_before ? $post_before->post_status : 'new';

	$user = get_user_by( 'id', $post->post_author );
	if ( ! $user ) {
		return;
	}

	$new         = get_post_status_object( $new_status );
	$user_handle = sprintf(
		'@%s',
		$user->user_login
	);

	$msg = '';
	if ( $new_status === $old_status ) {
		return;
	} elseif ( 'new' === $old_status && PENDING_STATUS === $new_status ) {
		$msg = sprintf(
			// translators: User name.
			__( 'New flag submitted by %s', 'wporg-patterns' ),
			esc_html( $user_handle ),
		);
	} elseif ( PENDING_STATUS === $new_status ) {
		$msg = sprintf(
			// translators: 1. User name; 2. Post status.
			__( 'Flag submitted by %1$s set to %2$s', 'wporg-patterns' ),
			esc_html( $user_handle ),
			esc_html( $new->label )
		);
	} elseif ( RESOLVED_STATUS === $new_status ) {
		$msg = sprintf(
			// translators: 1. User name; 2. Post status.
			__( 'Flag submitted by %1$s marked as %2$s', 'wporg-patterns' ),
			esc_html( $user_handle ),
			esc_html( $new->label )
		);
	} elseif ( 'trash' === $new_status ) {
		$msg = sprintf(
			// translators: User name.
			__( 'Flag submitted by %s moved to trash.', 'wporg-patterns' ),
			esc_html( $user_handle )
		);
	}

	if ( $msg ) {
		$data = array(
			'post_excerpt' => $msg,
			'post_type'    => InternalNotes\LOG_POST_TYPE,
		);

		InternalNotes\create_note( $pattern_id, $data );
	}
}
