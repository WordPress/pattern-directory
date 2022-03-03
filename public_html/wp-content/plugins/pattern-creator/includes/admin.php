<?php

namespace WordPressdotorg\Pattern_Creator\Admin;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

defined( 'WPINC' ) || die();

add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu' );
add_action( 'admin_init', __NAMESPACE__ . '\admin_init' );
add_action( 'admin_bar_menu', __NAMESPACE__ . '\filter_admin_bar_links', 500 ); // 500 to run after all items are added to the menu.

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

/**
 * Filter the admin bar links to direct to the Pattern Creator.
 *
 * @param WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
 */
function filter_admin_bar_links( $wp_admin_bar ) {
	// "New Block Pattern" link.
	$new_pattern = $wp_admin_bar->get_node( 'new-wporg-pattern' );
	if ( $new_pattern ) {
		$new_pattern->href = site_url( 'new-pattern/' );
		$wp_admin_bar->remove_node( $new_pattern->id );
		$wp_admin_bar->add_node( $new_pattern );
	}

	// Top-level "+ New" link, if New Block Pattern is the only item.
	$new_content = $wp_admin_bar->get_node( 'new-content' );
	if ( $new_content && str_contains( $new_content->href, POST_TYPE ) ) {
		$new_content->href = site_url( 'new-pattern/' );
		$wp_admin_bar->remove_node( $new_content->id );
		$wp_admin_bar->add_node( $new_content );
	}

	// "Edit Block Pattern" link.
	if ( is_singular( POST_TYPE ) ) {
		$edit_pattern = $wp_admin_bar->get_node( 'edit' );
		if ( $edit_pattern ) {
			$pattern_id = wp_get_post_parent_id() ?: get_the_ID();
			$edit_pattern->href = site_url( "pattern/$pattern_id/edit/" );
			if ( wp_get_post_parent_id() !== 0 ) {
				$edit_pattern->title = __( 'Edit Original Pattern', 'wporg-patterns' );
			}
			$wp_admin_bar->remove_node( $edit_pattern->id );
			$wp_admin_bar->add_node( $edit_pattern );
		}
	}
}
