<?php

namespace WordPressdotorg\Pattern_Directory\Admin;

use WP_Post;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\POST_TYPE as FLAG;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_REASON;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\PENDING_STATUS;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\RESOLVED_STATUS;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'manage_' . FLAG . '_posts_columns', __NAMESPACE__ . '\flag_list_table_columns' );
add_action( 'manage_' . FLAG . '_posts_custom_column', __NAMESPACE__ . '\flag_list_table_render_custom_columns', 10, 2 );
add_filter( 'display_post_states', __NAMESPACE__ . '\flag_list_table_post_states', 10, 2 );
add_filter( 'post_row_actions', __NAMESPACE__ . '\flag_list_table_row_actions', 10, 2 );
add_filter( 'bulk_actions-edit-wporg-pattern-flag', __NAMESPACE__ . '\flag_list_table_bulk_actions' );
add_filter( 'handle_bulk_actions-edit-wporg-pattern-flag', __NAMESPACE__ . '\flag_list_table_handle_bulk_actions', 10, 3 );
add_action( 'admin_menu', __NAMESPACE__ . '\flag_reason_submenu_page' );
add_filter( 'submenu_file', __NAMESPACE__ . '\flag_reason_submenu_highlight', 10, 2 );

/**
 * Modify the flags list table columns and their order.
 *
 * @param array $columns
 *
 * @return array
 */
function flag_list_table_columns( $columns ) {
	$block_pattern = get_post_type_object( PATTERN );
	$flag_reason   = get_taxonomy( FLAG_REASON );

	$cb = array(
		'cb' => $columns['cb'],
	);

	$front_columns = array(
		'pattern'                            => $block_pattern->labels->singular_name,
		'taxonomy-wporg-pattern-flag-reason' => $flag_reason->labels->singular_name,
		'details'                            => __( 'Details', 'wporg-patterns' ),
	);

	$columns['author'] = __( 'Reporter', 'wporg-patterns' );

	unset( $columns['cb'] );
	unset( $columns['title'] );
	unset( $columns['taxonomy-wporg-pattern-flag-reason'] );

	$columns = $cb + $front_columns + $columns;

	return $columns;
}

/**
 * Render the contents of custom list table columns.
 *
 * @param string $column_name
 * @param int    $post_id
 *
 * @return void
 */
function flag_list_table_render_custom_columns( $column_name, $post_id ) {
	global $wp_list_table;

	$current_flag = get_post( $post_id );
	$pattern      = get_post( $current_flag->post_parent );

	switch ( $column_name ) {
		case 'pattern':
			$status = get_post_status( $current_flag );
			if ( PENDING_STATUS === $status ) {
				$title_wrapper = '<strong>%s</strong>';
			} else {
				$title_wrapper = '<span>%s</span>';
			}

			printf(
				$title_wrapper, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( _draft_or_post_title( $pattern ) )
			);

			_post_states( $current_flag );
			break;

		case 'details':
			echo wp_kses_data( get_the_excerpt( $current_flag ) );
			break;
	}
}

/**
 * Modify the post states for the flags list table.
 *
 * @param array   $post_states
 * @param WP_Post $post
 *
 * @return array
 */
function flag_list_table_post_states( $post_states, $post ) {
	if ( FLAG === get_post_type( $post ) && RESOLVED_STATUS === get_post_status( $post ) ) {
		$status_obj = get_post_status_object( RESOLVED_STATUS );
		$post_states[ RESOLVED_STATUS ] = $status_obj->label;
	}

	return $post_states;
}

/**
 * Set up row actions for pattern flags list table.
 *
 * @param array   $actions
 * @param WP_Post $post
 *
 * @return array
 */
function flag_list_table_row_actions( $actions, $post ) {
	if ( FLAG !== get_post_type( $post ) ) {
		return $actions;
	}

	$current_screen = get_current_screen();
	$screen_file    = add_query_arg(
		'post_type',
		FLAG,
		'edit.php'
	);

	$actions = array();

	$pattern       = get_post( $post->post_parent );
	$pattern_title = _draft_or_post_title( $pattern );
	$pattern_url   = add_query_arg(
		array(
			'post'   => $pattern->ID,
			'action' => 'edit',
		),
		admin_url( 'post.php' )
	);

	$actions['review'] = sprintf(
		'<a href="%s" aria-label="%s">%s</a>',
		esc_attr( $pattern_url ),
		/* translators: %s: Post title. */
		esc_attr( sprintf( __( 'Review &#8220;%s&#8221;', 'wporg-patterns' ), $pattern_title ) ),
		__( 'Review Pattern', 'wporg-patterns' )
	);

	if ( PENDING_STATUS === get_post_status( $post ) ) {
		$resolve_url = add_query_arg(
			array(
				'action' => 'resolve',
				'post'   => array( $post->ID ),
			),
			wp_nonce_url( $screen_file, 'bulk-posts' )
		);

		$actions['resolve'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_attr( $resolve_url ),
			esc_attr( __( 'Mark this flag as resolved', 'wporg-patterns' ) ),
			__( 'Resolve', 'wporg-patterns' )
		);
	}

	if ( RESOLVED_STATUS === get_post_status( $post ) ) {
		$unresolve_url = add_query_arg(
			array(
				'action' => 'unresolve',
				'post'   => array( $post->ID ),
			),
			wp_nonce_url( $screen_file, 'bulk-posts' )
		);

		$actions['unresolve'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			esc_attr( $unresolve_url ),
			esc_attr( __( 'Mark this flag as pending', 'wporg-patterns' ) ),
			__( 'Unresolve', 'wporg-patterns' )
		);
	}

	return $actions;
}

/**
 * Define bulk actions for the flag list table.
 *
 * @param array $actions
 *
 * @return array
 */
function flag_list_table_bulk_actions( $actions ) {
	$actions = array(
		'resolve'   => __( 'Resolve', 'wporg-patterns' ),
		'unresolve' => __( 'Unresolve', 'wporg-patterns' ),
	);

	return $actions;
}

/**
 * Execute bulk actions for the flag list table.
 *
 * @param string $sendback
 * @param string $doaction
 * @param array  $post_ids
 *
 * @return mixed|string
 */
function flag_list_table_handle_bulk_actions( $sendback, $doaction, $post_ids ) {
	$post_data = array(
		'post_type' => FLAG,
		'post'      => $post_ids,
	);

	switch ( $doaction ) {
		case 'resolve':
			$post_data['_status'] = RESOLVED_STATUS;
			break;
		case 'unresolve':
			$post_data['_status'] = PENDING_STATUS;
			break;
	}

	$result = bulk_edit_posts( $post_data );

	if ( is_array( $result ) ) {
		$result['updated'] = count( $result['updated'] );
		$result['skipped'] = count( $result['skipped'] );
		$result['locked']  = count( $result['locked'] );
		$sendback          = add_query_arg( $result, $sendback );
	}

	return $sendback;
}

/**
 * Add the Flag Reason taxonomy page as a subpage of Block Pattern.
 *
 * WP won't do this on its own because Flag Reason is associated with the Pattern Flag post type rather than
 * the post type that we want to put it under.
 *
 * @return void
 */
function flag_reason_submenu_page() {
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
 * @param string $submenu_file
 *
 * @return string
 */
function flag_reason_submenu_highlight( $submenu_file, $parent_file ) {
	global $post_type, $taxonomy;

	if (
		'edit.php?post_type=wporg-pattern' === $parent_file
		&& PATTERN === $post_type
		&& FLAG_REASON === $taxonomy
	) {
		$submenu_file = 'edit-tags.php?taxonomy=' . FLAG_REASON . '&post_type=' . PATTERN;
	}

	return $submenu_file;
}
