<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN;

defined( 'WPINC' ) || die();

const POST_TYPE = 'wporg-pattern-flag';
const TAX_TYPE  = 'wporg-pattern-flag-reason';

/**
 * Actions and filters.
 */
add_action( 'init', __NAMESPACE__ . '\register_post_type_data' );
add_action( 'admin_menu', __NAMESPACE__ . '\taxonomy_submenu_page' );
add_filter( 'parent_file', __NAMESPACE__ . '\taxonomy_submenu_highlight' );

/**
 * Register entities for block pattern flags.
 *
 * @return void
 */
function register_post_type_data() {
	$post_type_labels = array(
		'name'                  => __( 'Block Pattern Flags', 'wporg-patterns' ),
		'singular_name'         => __( 'Block Pattern Flag', 'wporg-patterns' ),
		'add_new_item'          => __( 'Add New Flag', 'wporg-patterns' ),
		'edit_item'             => __( 'Edit Flag', 'wporg-patterns' ),
		'new_item'              => __( 'New Flag', 'wporg-patterns' ),
		'view_item'             => __( 'View Flag', 'wporg-patterns' ),
		'view_items'            => __( 'View Flags', 'wporg-patterns' ),
		'search_items'          => __( 'Search Flags', 'wporg-patterns' ),
		'not_found'             => __( 'No flags found.', 'wporg-patterns' ),
		'not_found_in_trash'    => __( 'No flags found in Trash.', 'wporg-patterns' ),
		'all_items'             => __( 'Flags', 'wporg-patterns' ),
		'insert_into_item'      => __( 'Insert into flag', 'wporg-patterns' ),
		'filter_items_list'     => __( 'Filter flags list', 'wporg-patterns' ),
		'items_list_navigation' => __( 'Flags list navigation', 'wporg-patterns' ),
		'items_list'            => __( 'Flags list', 'wporg-patterns' ),
	);

	register_post_type(
		POST_TYPE,
		array(
			'labels'                => $post_type_labels,
			'description'           => 'Flags are added to patterns by users when the pattern needs to be reviewed by a moderator.',
			'show_ui'               => true,
			'show_in_menu'          => 'edit.php?post_type=wporg-pattern',
			'show_in_admin_bar'     => false,
			'show_in_rest'          => true,
			'rest_controller_class' => '\\WordPressdotorg\\Pattern_Directory\\REST_Flags_Controller',
			'supports'              => array( 'author', 'excerpt' ),
			'can_export'            => false,
			'delete_with_user'      => false,
		)
	);

	$taxonomy_labels = array(
		'name'                       => __( 'Flag Reasons', 'wporg-patterns' ),
		'singular_name'              => __( 'Flag Reason', 'wporg-patterns' ),
		'search_items'               => __( 'Search Reasons', 'wporg-patterns' ),
		'all_items'                  => __( 'All Reasons', 'wporg-patterns' ),
		'edit_item'                  => __( 'Edit Reason', 'wporg-patterns' ),
		'view_item'                  => __( 'View Reason', 'wporg-patterns' ),
		'update_item'                => __( 'Update Reason', 'wporg-patterns' ),
		'add_new_item'               => __( 'Add New Reason', 'wporg-patterns' ),
		'new_item_name'              => __( 'New Reason', 'wporg-patterns' ),
		'separate_items_with_commas' => __( 'Separate reasons with commas', 'wporg-patterns' ),
		'add_or_remove_items'        => __( 'Add or remove reasons', 'wporg-patterns' ),
		'not_found'                  => __( 'No reasons found.', 'wporg-patterns' ),
		'no_terms'                   => __( 'No reasons', 'wporg-patterns' ),
		'filter_by_item'             => __( 'Filter by reason', 'wporg-patterns' ),
		'items_list_navigation'      => __( 'Reasons list navigation', 'wporg-patterns' ),
		'items_list'                 => __( 'Reasons list', 'wporg-patterns' ),
		'back_to_items'              => __( '&larr; Go to Reasons', 'wporg-patterns' ),
	);

	register_taxonomy(
		TAX_TYPE,
		POST_TYPE,
		array(
			'labels'             => $taxonomy_labels,
			'description'        => 'Flag reason indicates why a flag was added to a pattern.',
			'public'             => false,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=' . PATTERN,
			'show_in_rest'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'show_admin_column'  => true,
		)
	);
}

/**
 * Add the Flag Reason taxonomy page as a subpage of Block Pattern.
 *
 * WP won't do this on its own because Flag Reason is associated with the Pattern Flag post type rather than
 * the post type that we want to put it under.
 *
 * @return void
 */
function taxonomy_submenu_page() {
	$taxonomy = get_taxonomy( TAX_TYPE );

	add_submenu_page(
		'edit.php?post_type=wporg-pattern',
		__( 'Flag Reasons', 'wporg-patterns' ),
		__( 'Reasons', 'wporg-patterns' ),
		$taxonomy->cap->manage_terms,
		'edit-tags.php?taxonomy=' . TAX_TYPE . '&post_type=' . PATTERN,
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

	if ( PATTERN === $post_type && TAX_TYPE === $taxonomy ) {
		$plugin_page  = 'edit-tags.php?taxonomy=' . TAX_TYPE . '&post_type=' . PATTERN;
		$submenu_file = 'edit-tags.php?taxonomy=' . TAX_TYPE . '&post_type=' . PATTERN;
	}

	return $parent_file;
}
