<?php

namespace WordPressdotorg\Pattern_Directory\Admin;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

defined( 'WPINC' ) || die();

require_once __DIR__ . '/admin-flags.php';
require_once __DIR__ . '/admin-patterns.php';
require_once __DIR__ . '/admin-settings.php';
require_once __DIR__ . '/admin-stats.php';

/**
 * Actions and filters.
 */
add_action( 'admin_bar_menu', __NAMESPACE__ . '\filter_admin_bar_links', 500 ); // 500 to run after all items are added to the menu.

/**
 * Filter the admin bar links to direct to the Pattern Creator.
 *
 * @param \WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance, passed by reference.
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
