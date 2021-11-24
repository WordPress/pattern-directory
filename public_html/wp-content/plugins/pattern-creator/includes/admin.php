<?php

namespace WordPressdotorg\Pattern_Creator\Admin;

defined( 'WPINC' ) || die();

add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu' );
add_action( 'admin_init', __NAMESPACE__ . '\admin_init' );

const PAGE_SLUG = 'wporg-pattern-creator';
const SECTION_NAME = 'wporg-pattern-settings';

/**
 * Registers a new settings page under Settings.
 */
function admin_menu() {
	add_options_page(
		__( 'Block Patterns', 'wporg-patterns' ),
		__( 'Block Patterns', 'wporg-patterns' ),
		'manage_options',
		PAGE_SLUG,
		__NAMESPACE__ . '\render_page'
	);

}

/**
 * Registers a new settings page under Settings.
 */
function admin_init() {
	add_settings_section(
		SECTION_NAME,
		esc_html__( 'Editor Settings', 'wporg-patterns' ),
		'__return_empty_string',
		PAGE_SLUG
	);

	register_setting(
		SECTION_NAME,
		'wporg-pattern-default_status',
		array(
			'type' => 'string',
			'sanitize_callback' => function( $value ) {
				return in_array( $value, array( 'publish', 'pending' ) ) ? $value : 'publish';
			},
			'default' => 'publish',
		)
	);
	add_settings_field(
		'wporg-pattern-default_status',
		esc_html__( 'Default status of new patterns', 'wporg-patterns' ),
		__NAMESPACE__ . '\render_status_field',
		PAGE_SLUG,
		SECTION_NAME,
		array(
			'label_for' => 'wporg-pattern-default_status',
		)
	);
}

/**
 * Render a checkbox.
 */
function render_status_field() {
	$current = get_option( 'wporg-pattern-default_status' );
	$statii = array(
		'publish' => esc_html__( 'Published', 'wporg-patterns' ),
		'pending' => esc_html__( 'Pending', 'wporg-patterns' ),
	);

	echo '<select name="wporg-pattern-default_status" id="wporg-pattern-default_status" aria-describedby="wporg-pattern-default_status-help">';
	foreach ( $statii as $value => $label ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		printf( '<option value="%s" %s>%s</option>', $value, selected( $value, $current ), $label );
	}
	echo '</select>';
	printf( '<p id="wporg-pattern-default_status-help">%s</p>', esc_html__( 'Use this setting to control whether new patterns need moderation before showing up (pending) or not (published).', 'wporg-patterns' ) );
}

/**
 * Display the Block Patterns settings page.
 */
function render_page() {
	require_once dirname( __DIR__ ) . '/view/settings.php';
}
