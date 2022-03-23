<?php

namespace WordPressdotorg\Pattern_Directory\Stats;

use function WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\get_pattern_ids_with_pending_flags;
use const WordPressdotorg\Pattern_Directory\Favorite\META_KEY as FAVORITE_META_KEY;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN_POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\UNLISTED_STATUS;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\POST_TYPE as FLAG_POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_REASON;

defined( 'WPINC' ) || die();

/**
 * Constants.
 */
const STATS_POST_TYPE = 'wporg-pattern-stats'; // Must be <= 20 characters.
const VERSION         = 2; // Must be an integer.

/**
 * Actions and filters.
 */
add_action( 'init', __NAMESPACE__ . '\register_cpt' );
add_action( 'init', __NAMESPACE__ . '\register_meta_fields' );
add_action( 'init', __NAMESPACE__ . '\schedule_cron_job' );
add_action( PATTERN_POST_TYPE . '_record_snapshot', __NAMESPACE__ . '\record_snapshot' );

/**
 * Register a post type for storing snapshots.
 *
 * @return void
 */
function register_cpt() {
	register_post_type(
		STATS_POST_TYPE,
		array(
			'label'           => __( 'Pattern Stats Snapshot', 'wporg-patterns' ),
			'public'          => false,
			'show_in_rest'    => false,
			'capability-type' => STATS_POST_TYPE,
			'capabilities'    => array(
				'create_posts' => 'do_not_allow',
			),
			'supports'        => array( 'title', 'custom-fields' ),
		)
	);
}

/**
 * Define the post meta fields for the snapshot CPT.
 *
 * ⚠️ When you change the field schema, make sure you also bump up the VERSION constant at the top of this file.
 *
 * @return array
 */
function get_meta_field_schema() {
	return array(
		'type'       => 'object',
		'properties' => array(
			'count-patterns'                 => array(
				'description' => __( 'The total number of pattern posts.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-patterns_publish'         => array(
				'description' => __( 'The total number of published patterns.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-patterns_publish-originals'         => array(
				'description' => __( 'The total number of published original patterns (not translations or remixes).', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-patterns_publish-translations'         => array(
				'description' => __( 'The total number of published pattern translations.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-patterns_possible-spam'  => array(
				'description' => __( 'The total number of possibly spam patterns.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-patterns_unlisted'        => array(
				'description' => __( 'The total number of unlisted patterns.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-patterns_unlisted-spam'   => array(
				'description' => __( 'The total number of patterns unlisted due to spam.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-patterns_favorited'       => array(
				'description' => __( 'The total number of patterns with at least one favorite.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-patterns_flagged-pending' => array(
				'description' => __( 'The total number of patterns with a pending flag.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-favorites'                => array(
				'description' => __( 'The total number of favorites.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-flags'                    => array(
				'description' => __( 'The total number of flags.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-flags_pending'            => array(
				'description' => __( 'The total number of pending flags.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-flags_resolved'           => array(
				'description' => __( 'The total number of resolved flags.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'count-users_with-favorite'      => array(
				'description' => __( 'The total number of users with at least one favorited pattern.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'elapsed-time'                   => array(
				'description' => __( 'Number of milliseconds to generate the snapshot.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
			'version'                        => array(
				'description' => __( 'The version of the snapshot data schema.', 'wporg-patterns' ),
				'type'        => 'integer',
				'single'      => true,
			),
		),
	);
}

/**
 * Register the post meta fields for the snapshot CPT.
 *
 * @return void
 */
function register_meta_fields() {
	$schema = get_meta_field_schema();

	foreach ( $schema['properties'] as $field_name => $field_schema ) {
		register_post_meta( STATS_POST_TYPE, $field_name, $field_schema );
	}
}

/**
 * Schedule the cron job to record a stats snapshot.
 *
 * @return void
 */
function schedule_cron_job() {
	if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
		return;
	}

	if ( wp_next_scheduled( PATTERN_POST_TYPE . '_record_snapshot' ) ) {
		return;
	}

	// Schedule a repeating "single" event to avoid having to create a custom schedule.
	wp_schedule_single_event(
		strtotime( 'tomorrow' ), // 00:00 UTC.
		PATTERN_POST_TYPE . '_record_snapshot'
	);
}

/**
 * Generate a snapshot post.
 *
 * @return void
 */
function record_snapshot() {
	if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
		return;
	}

	$data = get_snapshot_data();

	wp_insert_post(
		array(
			'post_type'   => STATS_POST_TYPE,
			'post_author' => 0,
			'post_title'  => gmdate( 'Y-m-d' ),
			'post_status' => 'publish',
			'meta_input'  => $data,
		),
		true
	);
}

/**
 * Generate the data that will be added to a snapshot post as post meta.
 *
 * @return array
 */
function get_snapshot_data() {
	$data     = array();
	$start_ms = round( microtime( true ) * 1000 );
	$schema   = get_meta_field_schema();

	foreach ( array_keys( $schema['properties'] ) as $field_name ) {
		$func = __NAMESPACE__ . '\\callback_' . str_replace( '-', '_', $field_name );

		if ( is_callable( $func ) ) {
			$data[ $field_name ] = call_user_func( $func );
		}
	}

	$elapsed_ms = round( microtime( true ) * 1000 ) - $start_ms;

	$data['elapsed-time'] = (int) $elapsed_ms;
	$data['version']      = VERSION;

	return $data;
}

/**
 * Count the total number of pattern posts.
 *
 * @return int
 */
function callback_count_patterns() {
	$patterns_by_status = (array) wp_count_posts( PATTERN_POST_TYPE );

	// Exclude auto drafts since they aren't really pattern posts yet.
	unset( $patterns_by_status['auto-draft'] );

	return (int) array_sum( $patterns_by_status );
}

/**
 * Count the total number of published patterns.
 *
 * @return int
 */
function callback_count_patterns_publish() {
	$patterns_by_status = wp_count_posts( PATTERN_POST_TYPE );

	return $patterns_by_status->publish;
}

/**
 * Count the total number of published original patterns (not translations or remixes).
 *
 * @return int
 */
function callback_count_patterns_publish_originals() {
	$args = array(
		'post_type'   => PATTERN_POST_TYPE,
		'post_status' => 'publish',
		'post_parent' => 0,
		'numberposts' => 1,
	);

	$query = new \WP_Query( $args );

	return $query->found_posts;
}

/**
 * Count the total number of published pattern translations and remixes.
 *
 * @return int
 */
function callback_count_patterns_publish_translations() {
	$args = array(
		'post_type'           => PATTERN_POST_TYPE,
		'post_status'         => 'publish',
		'numberposts'         => 1,
		'meta_query'          => array(
			array(
				'key'   => 'wpop_is_translation',
				'value' => 1,
			),
		),
	);

	$query = new \WP_Query( $args );

	return $query->found_posts;
}

/**
 * Count the total number of pending-review patterns.
 *
 * @return int
 */
function callback_count_patterns_possible_spam() {
	$patterns_by_status = wp_count_posts( PATTERN_POST_TYPE );

	return $patterns_by_status->{'pending-review'};
}

/**
 * Count the total number of unlisted patterns.
 *
 * @return int
 */
function callback_count_patterns_unlisted() {
	$patterns_by_status = wp_count_posts( PATTERN_POST_TYPE );

	return $patterns_by_status->unlisted;
}

/**
 * Count the total number of patterns with the unlist reason "spam".
 *
 * @return int
 */
function callback_count_patterns_unlisted_spam() {
	$args = array(
		'post_type'   => PATTERN_POST_TYPE,
		'post_status' => UNLISTED_STATUS,
		'tax_query'  => array(
			array(
				'taxonomy' => FLAG_REASON,
				'field'    => 'slug',
				'terms'    => '4-spam',
			),
		),
		'numberposts' => 1, // We only need the `found_posts` value here.
	);

	$query = new \WP_Query( $args );

	return $query->found_posts;
}

/**
 * Count the total number of patterns with at least one favorite.
 *
 * @return int
 */
function callback_count_patterns_favorited() {
	$args = array(
		'post_type'   => PATTERN_POST_TYPE,
		'post_status' => 'any',
		'meta_query'  => array(
			array(
				'key'     => FAVORITE_META_KEY,
				'value'   => 0,
				'compare' => '>',
			),
		),
		'numberposts' => 1, // We only need the `found_posts` value here.
	);

	$query = new \WP_Query( $args );

	return $query->found_posts;
}

/**
 * Count the total number of patterns with a pending flag.
 *
 * @return int
 */
function callback_count_patterns_flagged_pending() {
	$post_ids = get_pattern_ids_with_pending_flags();

	return count( $post_ids );
}

/**
 * Count the total number of favorites.
 *
 * @return int
 */
function callback_count_favorites() {
	global $wpdb;

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$count = $wpdb->get_var( $wpdb->prepare(
		"
		SELECT COUNT(*)
		FROM {$wpdb->usermeta}
		WHERE meta_key=%s
		",
		FAVORITE_META_KEY,
	) );
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return absint( $count );
}

/**
 * Count the total number of flags.
 *
 * @return int
 */
function callback_count_flags() {
	$flags_by_status = (array) wp_count_posts( FLAG_POST_TYPE );

	return (int) array_sum( $flags_by_status );
}

/**
 * Count the total number of pending flags.
 *
 * @return int
 */
function callback_count_flags_pending() {
	$flags_by_status = wp_count_posts( FLAG_POST_TYPE );

	return $flags_by_status->pending;
}

/**
 * Count the total number of resolved flags.
 *
 * @return int
 */
function callback_count_flags_resolved() {
	$flags_by_status = wp_count_posts( FLAG_POST_TYPE );

	return $flags_by_status->resolved;
}

/**
 * Count the total number of users with at least one favorited pattern.
 *
 * @return int
 */
function callback_count_users_with_favorite() {
	global $wpdb;

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$user_ids = $wpdb->get_col( $wpdb->prepare(
		"
		SELECT DISTINCT user_id
		FROM {$wpdb->usermeta}
		WHERE meta_key=%s
		",
		FAVORITE_META_KEY,
	) );
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	return count( $user_ids );
}

/**
 * Get snapshot posts.
 *
 * @param array $args     Optional. Query args to refine the dataset.
 * @param bool  $wp_query Optional. True to return the WP_Query object instead of an array of post objects.
 *
 * @return int[]|\WP_Post[]|\WP_Query
 */
function get_snapshots( $args = array(), $wp_query = false ) {
	$args = wp_parse_args(
		$args,
		array(
			'orderby' => 'date',
			'order'   => 'asc',
		)
	);

	$args['post_type']   = STATS_POST_TYPE;
	$args['post_status'] = 'publish';

	$query = new \WP_Query( $args );

	if ( true === $wp_query ) {
		return $query;
	}

	return $query->get_posts();
}
