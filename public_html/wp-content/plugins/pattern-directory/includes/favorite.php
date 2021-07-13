<?php

namespace WordPressdotorg\Pattern_Directory\Favorite;
use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\get_block_pattern;

// Used for both the post meta (count of favorites) and user meta (list of pattern IDs).
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

/**
 * Get the cached count of how many times this pattern has been favorited.
 *
 * @param int|WP_Post $post The pattern to check.
 * @return integer
 */
function get_favorite_count( $post = 0 ) {
	$post = get_block_pattern( $post );
	if ( ! $post ) {
		return false;
	}

	return absint( get_post_meta( $post->ID, META_KEY, true ) );
}

/**
 * Get a count of how many times this pattern has been favorited, directly from the users table.
 *
 * @param int|WP_Post $post The pattern to check.
 * @return integer
 */
function get_raw_favorite_count( $post = 0 ) {
	global $wpdb;
	$post = get_block_pattern( $post );
	if ( ! $post ) {
		return false;
	}
	$count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*)
			FROM {$wpdb->usermeta}
			WHERE meta_key=%s
			AND meta_value=%d",
		META_KEY,
		$post->ID
	) );

	return absint( $count );
}

/**
 * Update a given post's favorite count cache.
 *
 * @param mixed $post_id The post ID.
 */
function update_favorite_cache( $post_id ) {
	$count = get_raw_favorite_count( $post_id );
	if ( ! is_int( $count ) ) {
		return;
	}

	update_post_meta( $post_id, META_KEY, $count );
}

/**
 * Trigger the update of favorite count when a user favorites or unfavorites a pattern.
 *
 * @param int    $mid         The meta ID.
 * @param int    $user_id     User ID for this metadata.
 * @param string $meta_key    Metadata key.
 * @param mixed  $_meta_value Metadata value. Serialized if non-scalar. Post ID(s).
 */
function trigger_favorite_cache_update( $mid, $user_id, $meta_key, $_meta_value ) {
	if ( META_KEY !== $meta_key ) {
		return;
	}

	// This value can be an array in the delete action, so walk through each unique value and refresh the cache.
	if ( is_array( $_meta_value ) ) {
		$_meta_value = array_unique( $_meta_value );
		array_walk( $_meta_value, __NAMESPACE__ . '\update_favorite_cache' );
		return;
	}

	update_favorite_cache( $_meta_value );
}
add_action( 'added_user_meta', __NAMESPACE__ . '\trigger_favorite_cache_update', 10, 4 );
add_action( 'deleted_user_meta', __NAMESPACE__ . '\trigger_favorite_cache_update', 10, 4 );
