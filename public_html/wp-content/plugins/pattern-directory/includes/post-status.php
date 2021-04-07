<?php

namespace WordPressdotorg\Pattern_Directory\Post_Status;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_action( 'init', __NAMESPACE__ . '\register_post_statuses' );

/**
 * Register custom statuses for patterns.
 *
 * @return void
 */
function register_post_statuses() {
	register_post_status(
		'declined',
		array(
			'label'     => __( 'Declined', 'wporg-patterns' ),
			'public'    => false,
			'protected' => true,
		)
	);

	register_post_status(
		'removed',
		array(
			'label'     => __( 'Removed', 'wporg-patterns' ),
			'public'    => false,
			'protected' => true,
		)
	);
}
