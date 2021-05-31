<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type;

use WP_Post, WP_Query;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN;

defined( 'WPINC' ) || die();

const POST_TYPE       = 'wporg-pattern-flag';
const TAX_TYPE        = 'wporg-pattern-flag-reason';
const PENDING_STATUS  = 'pending';
const RESOLVED_STATUS = 'resolved';

/**
 * Actions and filters.
 */
add_action( 'init', __NAMESPACE__ . '\register_post_type_data' );
add_action( 'wp_insert_post', __NAMESPACE__ . '\check_flag_threshold', 10, 3 );

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
		'all_items'             => __( 'All Flags', 'wporg-patterns' ),
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
		'parent_item'                => __( 'Parent Reason', 'wporg-patterns' ),
		'parent_item_colon'          => __( 'Parent Reason:', 'wporg-patterns' ),
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

	register_post_status(
		RESOLVED_STATUS,
		array(
			'label'       => __( 'Resolved', 'wporg-patterns' ),
			'label_count' => _n_noop(
				'Resolved <span class="count">(%s)</span>',
				'Resolved <span class="count">(%s)</span>',
				'wporg-patterns'
			),
			'protected'   => true,
		)
	);
}

/**
 * Automatically unpublish a pattern if it receives a certain number of flags.
 *
 * @param int     $post_ID
 * @param WP_Post $post
 * @param bool    $update
 *
 * @return void
 */
function check_flag_threshold( $post_ID, $post, $update ) {
	if ( $update || POST_TYPE !== get_post_type( $post ) ) {
		return;
	}

	$pattern = get_post( $post->post_parent );
	if ( ! $pattern ) {
		return;
	}

	$flag_check = new WP_Query( array(
		'post_type'   => POST_TYPE,
		'post_parent' => $pattern->ID,
		'post_status' => PENDING_STATUS,
	) );

	$threshold = 3;

	if ( $flag_check->found_posts >= $threshold ) {
		wp_update_post( array(
			'ID'          => $pattern->ID,
			'post_status' => 'pending',
		) );
	}
}
