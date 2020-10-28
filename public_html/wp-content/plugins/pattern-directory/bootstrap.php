<?php
/**
 * Plugin Name: Pattern Directory
 * Description: Creates a directory to manage block patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 */

namespace WordPressdotorg\Pattern_Directory;

/**
 * Registers block editor 'wp_template_part' post type.
 */
function register_post_type() {
	\register_post_type(
		'wp-pattern',
		array(
			'public'        => true,
			'label'         => 'Block Pattern',
			'show_in_rest'  => true,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\register_post_type' );
