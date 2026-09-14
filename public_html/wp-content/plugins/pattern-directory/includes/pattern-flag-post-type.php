<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type;

use WP_Post;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE as PATTERN, SPAM_STATUS };

defined( 'WPINC' ) || die();

const POST_TYPE       = 'wporg-pattern-flag';
const TAX_TYPE        = 'wporg-pattern-flag-reason';
const PENDING_STATUS  = 'pending';
const RESOLVED_STATUS = 'resolved';

/**
 * Actions and filters.
 */
add_action( 'init', __NAMESPACE__ . '\register_post_type_data' );
add_action( 'wp_after_insert_post', __NAMESPACE__ . '\check_flag_threshold', 10, 3 );
add_action( 'transition_post_status', __NAMESPACE__ . '\resolve_flags_on_approval', 10, 3 );

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
		array( POST_TYPE, PATTERN ), // The taxonomy will also get applied to patterns when they get unlisted.
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
 * If a pattern or flag doesn't have a reason term added, but needs to show a reason description.
 *
 * @return string
 */
function get_default_reason_description() {
	return __( "This pattern doesn't meet the guidelines for the pattern directory.", 'wporg-patterns' );
}

/**
 * Hold a pattern for moderator review when it reaches the report threshold.
 *
 * Unlike `pending`, the review status prevents authors from republishing the pattern.
 *
 * @param int     $post_ID
 * @param WP_Post $post
 * @param bool    $update
 */
function check_flag_threshold( $post_ID, $post, $update ) {
	if ( $update || POST_TYPE !== get_post_type( $post ) ) {
		return;
	}

	$pattern = get_post( $post->post_parent );
	if ( ! $pattern ) {
		return;
	}

	// Nothing to take down, or a moderator's decision that outranks the report count.
	if ( ! in_array( $pattern->post_status, array( 'publish', 'pending' ), true ) ) {
		return;
	}

	if ( has_reached_flag_threshold( $pattern->ID ) ) {
		wp_update_post(
			array(
				'ID'          => $pattern->ID,
				'post_status' => SPAM_STATUS,
			)
		);

		/**
		 * Fires after a pattern is automatically unpublished for review.
		 *
		 * @param WP_Post $pattern The just-unpublished pattern.
		 */
		do_action( 'wporg_unlist_pattern', $pattern );
	}
}

/**
 * Resolve reports when a moderator republishes a pattern that reached the threshold.
 *
 * This resets the removal count. Reports below the threshold remain pending for separate review.
 *
 * @param string  $new_status The status the pattern moved to.
 * @param string  $old_status The status it moved from.
 * @param WP_Post $post       The pattern.
 */
function resolve_flags_on_approval( $new_status, $old_status, $post ) {
	if ( PATTERN !== get_post_type( $post ) || 'publish' !== $new_status || $new_status === $old_status ) {
		return;
	}

	// An author publishing their own pattern answers nothing, so their reports stay pending.
	if ( ! current_user_can( get_post_type_object( PATTERN )->cap->edit_others_posts ) ) {
		return;
	}

	if ( ! has_reached_flag_threshold( $post->ID ) ) {
		return;
	}

	$flags = get_posts(
		array(
			'post_type'   => POST_TYPE,
			'post_parent' => $post->ID,
			'post_status' => PENDING_STATUS,
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);

	foreach ( $flags as $flag_id ) {
		wp_update_post(
			array(
				'ID'          => $flag_id,
				'post_status' => RESOLVED_STATUS,
			)
		);
	}
}

/**
 * Check whether a pattern has reached the report threshold.
 *
 * @param int $pattern_id The reported pattern.
 *
 * @return bool True if the pattern's unresolved reports have reached the threshold.
 */
function has_reached_flag_threshold( $pattern_id ) {
	return count_pending_flag_reporters( $pattern_id ) >= get_flag_threshold();
}

/**
 * Get the report threshold, falling back to five for out-of-range values.
 *
 * @return int The report threshold.
 */
function get_flag_threshold() {
	$threshold = absint( get_option( 'wporg-pattern-flag_threshold', 5 ) );

	return ( $threshold >= 1 && $threshold <= 100 ) ? $threshold : 5;
}

/**
 * Count distinct reporters with unresolved flags against a pattern.
 *
 * Concurrent requests can create duplicate flags, so each reporter counts only once.
 *
 * @param int $pattern_id The flagged pattern.
 *
 * @return int The number of users holding an unresolved flag against the pattern.
 */
function count_pending_flag_reporters( $pattern_id ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- counting distinct authors, which WP_Query can't express.
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT COUNT( DISTINCT post_author )
			FROM {$wpdb->posts}
			WHERE post_type = %s
				AND post_parent = %d
				AND post_status = %s
			",
			POST_TYPE,
			$pattern_id,
			PENDING_STATUS
		)
	);
}

/**
 * Get a list of post IDs for patterns that have pending flags.
 *
 * TODO this isn't used anywhere on the front end, but maybe it should be cached?
 *
 * @param array $args Optional. Query args. 'orderby' and/or 'order'.
 *
 * @return int[]
 */
function get_pattern_ids_with_pending_flags( $args = array() ) {
	global $wpdb;

	$args = wp_parse_args(
		$args,
		array(
			'orderby' => 'date',
			'order'   => 'desc',
		)
	);

	// For string interpolation.
	$pattern = PATTERN;
	$flag    = POST_TYPE;

	// Allowlist orderby/order; these are a column and a keyword, which can't be bound as placeholders.
	$orderby_columns = array(
		'date'  => 'patterns.post_date',
		'title' => 'patterns.post_title',
		'id'    => 'patterns.ID',
	);
	$orderby_key     = is_string( $args['orderby'] ) ? strtolower( $args['orderby'] ) : '';
	$orderby         = $orderby_columns[ $orderby_key ] ?? 'patterns.post_date';
	$order           = ( is_string( $args['order'] ) && 'asc' === strtolower( $args['order'] ) ) ? 'ASC' : 'DESC';

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$pattern_ids = $wpdb->get_col(
		"
		SELECT DISTINCT patterns.ID
		FROM {$wpdb->posts} patterns
			JOIN {$wpdb->posts} flags ON patterns.ID = flags.post_parent
				AND flags.post_type = '{$flag}'
			    AND flags.post_status = 'pending'
		WHERE patterns.post_type = '{$pattern}'
		ORDER BY {$orderby} {$order}
		"
	);
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

	return $pattern_ids;
}
