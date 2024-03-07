<?php

namespace WordPressdotorg\Pattern_Directory\Openverse;

add_action( 'rest_api_init', __NAMESPACE__ . '\api_init' );

/**
 * Set up any custom endpoints.
 */
function api_init() {
	require_once __DIR__ . '/openverse-client.php';
	require_once __DIR__ . '/openverse-rest-controller.php';
	$controller = new \Openverse_REST_Controller();
	$controller->register_routes();

	// Allow the post type labels through the `types` endpoint when viewing.
	// This passes the value back to unauthenticated users, which prevents JS
	// errors when the post-date block tries to use them.
	register_rest_field(
		'type', // The object-type for the `types` endpoint.
		'labels',
		array(
			'schema' => array(
				'description' => __( 'Human-readable labels for the post type for various contexts.', 'wporg-patterns' ),
				'type'        => 'object',
				'context'     => array( 'edit', 'view' ),
				'readonly'    => true,
			),
		)
	);
}
