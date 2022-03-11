<?php

namespace WordPressdotorg\Pattern_Directory\Logging;

use WordPressdotorg\InternalNotes;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN_POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ PENDING_STATUS, RESOLVED_STATUS, POST_TYPE as FLAG_POST_TYPE };

/**
 * Actions and filters.
 */
add_action( 'transition_post_status', __NAMESPACE__ . '\flag_status_change', 10, 3 );

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
 * @param string   $new_status
 * @param string   $old_status
 * @param \WP_Post $post
 *
 * @return void
 */
function flag_status_change( $new_status, $old_status, $post ) {
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

	$new = get_post_status_object( $new_status );
	$user = get_user_by( 'id', $post->post_author );
	$user_handle = sprintf(
		'@%s',
		$user->user_login
	);

	$msg = '';
	if ( $new_status === $old_status ) {
		return;
	} elseif ( 'new' === $old_status && PENDING_STATUS === $new_status ) {
		$msg = sprintf(
			// translators: User name;
			__( 'New flag submitted by %s', 'wporg-patterns' ),
			esc_html( $user_handle ),
		);
	} elseif ( PENDING_STATUS === $new_status ) {
		$msg = sprintf(
			// translators: 1. User name; 2. Post status;
			__( 'Flag submitted by %1$s set to %2$s', 'wporg-patterns' ),
			esc_html( $user_handle ),
			esc_html( $new->label )
		);
	} elseif ( RESOLVED_STATUS === $new_status ) {
		$msg = sprintf(
			// translators: 1. User name; 2. Post status;
			__( 'Flag submitted by %1$s marked as %2$s', 'wporg-patterns' ),
			esc_html( $user_handle ),
			esc_html( $new->label )
		);
	} elseif ( 'trash' === $new_status ) {
		$msg = sprintf(
			// translators: User name;
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
