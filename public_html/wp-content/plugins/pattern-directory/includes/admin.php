<?php

namespace WordPressdotorg\Pattern_Directory\Admin;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_REASON;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\taxonomy_submenu_page' );
add_filter( 'parent_file', __NAMESPACE__ . '\taxonomy_submenu_highlight' );

/**
 * Add the Flag Reason taxonomy page as a subpage of Block Pattern.
 *
 * WP won't do this on its own because Flag Reason is associated with the Pattern Flag post type rather than
 * the post type that we want to put it under.
 *
 * @return void
 */
function taxonomy_submenu_page() {
	$taxonomy = get_taxonomy( FLAG_REASON );

	add_submenu_page(
		'edit.php?post_type=wporg-pattern',
		__( 'Flag Reasons', 'wporg-patterns' ),
		__( 'Reasons', 'wporg-patterns' ),
		$taxonomy->cap->manage_terms,
		'edit-tags.php?taxonomy=' . FLAG_REASON . '&post_type=' . PATTERN,
		null
	);
}

/**
 * Make sure the Reasons submenu item is highlighted when editing terms.
 *
 * @param string $parent_file
 *
 * @return string
 */
function taxonomy_submenu_highlight( $parent_file ) {
	global $plugin_page, $submenu_file, $post_type, $taxonomy;

	if ( PATTERN === $post_type && FLAG_REASON === $taxonomy ) {
		$plugin_page  = 'edit-tags.php?taxonomy=' . FLAG_REASON . '&post_type=' . PATTERN;
		$submenu_file = 'edit-tags.php?taxonomy=' . FLAG_REASON . '&post_type=' . PATTERN;
	}

	return $parent_file;
}
