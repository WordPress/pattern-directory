<?php
namespace WordPressdotorg\Pattern_Directory\Badges;

use function WordPressdotorg\Profiles\{ assign_badge, remove_badge };
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN_POST_TYPE;

/**
 * Actions and filters.
 */
add_action( 'transition_post_status', __NAMESPACE__ . '\status_transitions', 10, 3 );
add_action( 'remove_user_from_blog', __NAMESPACE__ . '\remove_user_from_blog', 10, 1 );
add_action( 'set_user_role', __NAMESPACE__ . '\set_user_role', 10, 2 );

function status_transitions( $new_status, $old_status, $post ) {
	$post = get_post( $post );

	if ( PATTERN_POST_TYPE !== get_post_type( $post ) ) {
		return;
	}

	if ( ! function_exists( 'WordPressdotorg\Profiles\assign_badge' ) ) {
		return;
	}

	if ( 'publish' === $new_status ) {
		assign_badge( 'pattern-author', $post->post_author );
	} elseif ( 'publish' === $old_status ) {
		// If the user has no published patterns, remove the badge.
		$other_posts = get_posts( [
			'post_type'   => PATTERN_POST_TYPE,
			'post_status' => 'publish',
			'author'      => $post->post_author,
			'exclude'     => $post->ID,
			'numberposts' => 1,
			'fields'      => 'ids',
		] );

		if ( ! $other_posts ) {
			remove_badge( 'pattern-author', $post->post_author );
		}
	}
}

function remove_user_from_blog( $user_id ) {
	if ( function_exists( 'WordPressdotorg\Profiles\remove_badge' ) ) {
		remove_badge( 'patterns-team', $user_id );
	}
}

function set_user_role( $user_id, $role ) {
	if ( ! function_exists( 'WordPressdotorg\Profiles\assign_badge' ) ) {
		return;
	}

	if ( 'subscriber' === $role || 'contributor' === $role ) {
		remove_badge( 'patterns-team', $user_id );
	} else {
		assign_badge( 'patterns-team', $user_id );
	}
}