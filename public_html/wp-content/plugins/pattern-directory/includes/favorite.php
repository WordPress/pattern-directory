<?php

namespace WordPressdotorg\Pattern_Directory\Favorite;
use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\get_block_pattern;

const META_KEY = 'wporg-pattern-favorites';

/**
 * Save a pattern to a users's favorites list.
 *
 * @param mixed   $post     The block pattern to favorite.
 * @param mixed   $user     The user favoriting. Optional. Default current user.
 * @param boolean $favorite Whether it's a favorite, or unfavorite. Optional. Default true.
 * @return boolean
 */
function save_favorite( $post, $user = 0, $favorite = true ) {
	$post = get_block_pattern( $post );
	$user = new \WP_User( $user ?: get_current_user_id() );
	if ( ! $post || ! $user->exists() ) {
		return false;
	}

	$users_favorites   = get_user_meta( $user->ID, META_KEY, true ) ?: array();
	$already_favorited = in_array( $post->ID, $users_favorites, true );

	if ( $favorite && $already_favorited ) {
		return true;
	} elseif ( $favorite ) {
		$users_favorites[] = $post->ID;
	} elseif ( ! $favorite && $already_favorited ) {
		unset( $users_favorites[ array_search( $post->ID, $users_favorites, true ) ] );
	} else {
		return true;
	}

	return update_user_meta( $user->ID, META_KEY, array_values( $users_favorites ) );
}

/**
 * Check if a pattern is in a user's favorites.
 *
 * @param mixed $post The block pattern to look up.
 * @param mixed $user The user to check. Optional. Default current user.
 * @return boolean
 */
function is_favorite( $post, $user = 0 ) {
	$post = get_block_pattern( $post );
	$user = new \WP_User( $user ?: get_current_user_id() );
	if ( ! $post || ! $user->exists() ) {
		return false;
	}

	$users_favorites   = get_user_meta( $user->ID, META_KEY, true ) ?: array();
	return in_array( $post->ID, $users_favorites, true );
}

/**
 * Get a list of the user's favorite patterns
 *
 * @param mixed $user The user to check. Optional. Default current user.
 * @return integer[]
 */
function get_favorites( $user = 0 ) {
	$user = new \WP_User( $user ?: get_current_user_id() );
	if ( ! $user->exists() ) {
		return array();
	}
	$favorites = get_user_meta( $user->ID, META_KEY, true ) ?: array();

	return array_map( 'absint', $favorites );
}
