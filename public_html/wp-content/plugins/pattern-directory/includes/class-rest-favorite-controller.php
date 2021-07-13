<?php

namespace WordPressdotorg\Pattern_Directory\Favorites_API;

use function WordPressdotorg\Pattern_Directory\Favorite\{add_favorite, get_favorite_count, get_favorites, remove_favorite};
use WP_Error, WP_REST_Server, WP_REST_Response;

add_action( 'rest_api_init', __NAMESPACE__ . '\init' );

/**
 * Set up the endpoints for favoriting patterns.
 */
function init() {
	register_rest_route(
		'wporg/v1',
		'pattern-favorites',
		array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => __NAMESPACE__ . '\get_items',
			'permission_callback' => __NAMESPACE__ . '\permissions_check',
		)
	);

	$args = array(
		'id' => array(
			'validate_callback' => function( $param, $request, $key ) {
				return is_numeric( $param );
			},
		),
	);
	register_rest_route(
		'wporg/v1',
		'pattern-favorites',
		array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => __NAMESPACE__ . '\create_item',
			'args' => $args,
			'permission_callback' => __NAMESPACE__ . '\permissions_check',
		)
	);
	register_rest_route(
		'wporg/v1',
		'pattern-favorites',
		array(
			'methods' => WP_REST_Server::DELETABLE,
			'callback' => __NAMESPACE__ . '\delete_item',
			'args' => $args,
			'permission_callback' => __NAMESPACE__ . '\permissions_check',
		)
	);
}

/**
 * Check if a given request has access to favorites.
 * The only requirement for anything "favorite" is to be logged in.
 *
 * @return WP_Error|bool
 */
function permissions_check() {
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_authorization_required',
			__( 'You must be logged in to favorite a pattern.', 'wporg-patterns' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	return true;
}

/**
 * Get the list of favorites for the current user.
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return WP_Error|WP_REST_Response
 */
function get_items( $request ) {
	$favorites = get_favorites();
	return new WP_REST_Response( $favorites, 200 );
}

/**
 * Save a pattern to the user's favorites.
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return WP_Error|WP_REST_Response
 */
function create_item( $request ) {
	$pattern_id = $request['id'];
	$success = add_favorite( $pattern_id );

	if ( $success ) {
		$count = get_favorite_count( $pattern_id );
		return new WP_REST_Response( $count, 200 );
	}

	return new WP_Error(
		'favorite-failed',
		__( 'Unable to favorite this pattern.', 'wporg-patterns' ),
		array( 'status' => 500 )
	);
}

/**
 * Remove a pattern from the user's favorites.
 *
 * @param WP_REST_Request $request Full data about the request.
 * @return WP_Error|WP_REST_Response
 */
function delete_item( $request ) {
	$pattern_id = $request['id'];
	$success = remove_favorite( $pattern_id );

	if ( $success ) {
		$count = get_favorite_count( $pattern_id );
		return new WP_REST_Response( $count, 200 );
	}

	return new WP_Error(
		'unfavorite-failed',
		__( 'Unable to remove this pattern from your favorites.', 'wporg-patterns' ),
		array( 'status' => 500 )
	);
}
