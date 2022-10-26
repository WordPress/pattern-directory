<?php

namespace WordPressdotorg\Pattern_Directory\Admin\Flags;

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
add_filter( 'query_vars', __NAMESPACE__ . '\flag_list_table_query_vars' );
add_filter( 'manage_' . FLAG . '_posts_columns', __NAMESPACE__ . '\flag_list_table_columns' );
add_action( 'manage_' . FLAG . '_posts_custom_column', __NAMESPACE__ . '\flag_list_table_render_custom_columns', 10, 2 );
add_filter( 'display_post_states', __NAMESPACE__ . '\flag_list_table_post_states', 10, 2 );
add_filter( 'post_row_actions', __NAMESPACE__ . '\flag_list_table_row_actions', 10, 2 );
add_filter( 'bulk_actions-edit-wporg-pattern-flag', __NAMESPACE__ . '\flag_list_table_bulk_actions' );
add_filter( 'handle_bulk_actions-edit-wporg-pattern-flag', __NAMESPACE__ . '\flag_list_table_handle_bulk_actions', 10, 3 );
add_filter( 'views_edit-wporg-pattern-flag', __NAMESPACE__ . '\flag_list_table_views' );
add_filter( 'wp_count_posts', __NAMESPACE__ . '\flag_list_table_count_flags_for_pattern', 10, 2 );
add_filter( 'wp_untrash_post_status', __NAMESPACE__ . '\flag_untrash_status', 5, 2 ); // Low priority so it won't override "Undo".
add_filter( 'submenu_file', __NAMESPACE__ . '\flag_reason_submenu_highlight', 10, 2 );

/**
 * Adjust available query vars for the flag list table.
 *
 * The posts list table doesn't have a way to filter the query that determines which posts appear in the list table.
 * Instead, you have to modify the list of public query vars way down in the WP class. :/
 *
 * @param array $query_vars
 *
 * @return array
 */
function flag_list_table_query_vars( $query_vars ) {
	if ( ! is_admin() ) {
		return $query_vars;
	}

	$screen = get_current_screen();

	if ( 'edit-wporg-pattern-flag' === $screen->id ) {
		$query_vars[] = 'post_parent';
	}

	return $query_vars;
}

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

	$saved_actions = array_intersect_key( $actions, array_fill_keys( array( 'trash', 'untrash', 'delete' ), true ) );
	$actions       = array();

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

	$actions = $actions + $saved_actions;

	$parent = filter_input( INPUT_GET, 'post_parent', FILTER_VALIDATE_INT );
	if ( ! $parent ) {
		$view_all_url = add_query_arg(
			array(
				'post_type'   => FLAG,
				'post_parent' => $post->post_parent,
			),
			admin_url( 'edit.php' )
		);

		$actions['view-all'] = sprintf(
			'<br /><a href="%s" aria-label="%s">%s</a>',
			esc_attr( $view_all_url ),
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'View all flags for &#8220;%s&#8221;', 'wporg-patterns' ), $pattern_title ) ),
			__( 'View All Flags For This Pattern', 'wporg-patterns' )
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
	$saved_actions = array_intersect_key( $actions, array_fill_keys( array( 'trash', 'untrash', 'delete' ), true ) );

	$actions = array(
		'resolve'   => __( 'Resolve', 'wporg-patterns' ),
		'unresolve' => __( 'Unresolve', 'wporg-patterns' ),
	);

	return $actions + $saved_actions;
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
 * Rearrange the flag list table views.
 *
 * @param array $views
 *
 * @return array
 */
function flag_list_table_views( $views ) {
	unset( $views['mine'] );

	$parent_id = filter_input( INPUT_GET, 'post_parent', FILTER_VALIDATE_INT );
	if ( $parent_id ) {
		$views = array_map(
			// Add a post_parent parameter to each view's URL.
			function( $item ) use ( $parent_id ) {
				return preg_replace_callback(
					'|href=[\'"]+([^\'"]+)[\'"]+|',
					function( $matches ) use ( $parent_id ) {
						$old_url = wp_kses_decode_entities( $matches[1] );
						$new_url = add_query_arg( array( 'post_parent' => $parent_id ), $old_url );

						return sprintf(
							'href="%s"',
							$new_url
						);
					},
					$item
				);
			},
			$views
		);

		$post_type_obj = get_post_type_object( FLAG );
		$return = array(
			'return' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( 'post_type', FLAG, admin_url( 'edit.php' ) ) ),
				esc_html( $post_type_obj->labels->all_items )
			),
		);

		$parent_title      = _draft_or_post_title( $parent_id );
		$subtitle = array(
			'filtered' => sprintf(
				'<strong>%s</strong>',
				sprintf( __( 'Viewing flags for &#8220;%s&#8221;', 'wporg-patterns' ), $parent_title )
			),
		);

		$views = $subtitle + $views + $return;
	}

	if ( isset( $views['resolved'] ) ) {
		// Resolved comes after Pending, or if not listed, after All.
		$resolved = array( $views['resolved'] );
		unset( $views['resolved'] );

		$split        = 1 + array_search( ( isset( $views['pending'] ) ? 'pending' : 'all' ), array_keys( $views ), true );
		$views = array_merge( array_slice( $views, 0, $split ), $resolved, array_slice( $views, $split ) );
	}

	return $views;
}

/**
 * Update post counts when viewing only flags for a specific pattern.
 *
 * @param object $counts
 * @param string $post_type
 *
 * @return object
 */
function flag_list_table_count_flags_for_pattern( $counts, $post_type ) {
	global $wpdb;

	if ( FLAG !== $post_type ) {
		return $counts;
	}

	$pattern_id = filter_input( INPUT_GET, 'post_parent', FILTER_VALIDATE_INT );

	if ( ! $pattern_id ) {
		return $counts;
	}

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT post_status, COUNT( * ) AS num_posts
			FROM {$wpdb->posts}
			WHERE post_type = %s
			AND post_parent = %d
			GROUP BY post_status
			",
			FLAG,
			$pattern_id
		)
	);

	$empty_counts   = array_fill_keys( get_post_stati(), 0 );
	$updated_counts = wp_list_pluck( $results, 'num_posts', 'post_status' );
	$updated_counts = array_map( 'absint', $updated_counts );
	$updated_counts = array_merge( $empty_counts, $updated_counts );

	return (object) $updated_counts;
}

/**
 * Set untrashed flag posts to pending status instead of draft.
 *
 * @param string $new_status
 * @param int    $post_id
 *
 * @return string
 */
function flag_untrash_status( $new_status, $post_id ) {
	if ( FLAG === get_post_type( $post_id ) ) {
		$new_status = PENDING_STATUS;
	}

	return $new_status;
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
