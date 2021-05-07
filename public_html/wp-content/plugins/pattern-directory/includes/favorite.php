<?php

namespace WordPressdotorg\Pattern_Directory\Favorite;
use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\get_block_pattern;

const META_KEY = 'wporg-pattern-favorites';

/**
 * Add a pattern to a users's favorites list.
 *
 * @param int|WP_Post|null $post The block pattern to favorite.
 * @param int|WP_User|null $user The user favoriting. Optional. Default current user.
 * @return boolean
 */
function add_favorite( $post, $user = 0 ) {
	$post = get_block_pattern( $post );
	$user = new \WP_User( $user ?: get_current_user_id() );
	if ( ! $post || ! $user->exists() ) {
		return false;
	}

	$users_favorites   = get_favorites( $user );
	$already_favorited = in_array( $post->ID, $users_favorites, true );
	if ( $already_favorited ) {
		return true;
	}

	$success = add_user_meta( $user->ID, META_KEY, $post->ID );
	return (bool) $success;
}

/**
 * Remove a pattern from a users's favorites list.
 *
 * @param int|WP_Post|null $post The block pattern to unfavorite.
 * @param int|WP_User|null $user The user favoriting. Optional. Default current user.
 * @return boolean
 */
function remove_favorite( $post, $user = 0 ) {
	$post = get_block_pattern( $post );
	$user = new \WP_User( $user ?: get_current_user_id() );
	if ( ! $post || ! $user->exists() ) {
		return false;
	}

	$users_favorites   = get_favorites( $user );
	$already_favorited = in_array( $post->ID, $users_favorites, true );
	if ( ! $already_favorited ) {
		return true;
	}

	return delete_user_meta( $user->ID, META_KEY, $post->ID );
}

/**
 * Check if a pattern is in a user's favorites.
 *
 * @param int|WP_Post|null $post The block pattern to look up.
 * @param int|WP_User|null $user The user to check. Optional. Default current user.
 * @return boolean
 */
function is_favorite( $post, $user = 0 ) {
	$post = get_block_pattern( $post );
	if ( ! $post ) {
		return false;
	}

	$users_favorites = get_favorites( $user );
	return in_array( $post->ID, $users_favorites, true );
}

/**
 * Get a list of the user's favorite patterns
 *
 * @param int|WP_User|null $user The user to check. Optional. Default current user.
 * @return integer[]
 */
function get_favorites( $user = 0 ) {
	$user = new \WP_User( $user ?: get_current_user_id() );
	if ( ! $user->exists() ) {
		return array();
	}
	$favorites = get_user_meta( $user->ID, META_KEY ) ?: array();

	return array_map( 'absint', $favorites );
}
