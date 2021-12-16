<?php

namespace WordPressdotorg\Pattern_Directory\Admin\Stats;

use function WordPressdotorg\Pattern_Directory\Stats\{ get_meta_field_schema, get_snapshot_data, get_snapshots };
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN_POST_TYPE;

defined( 'WPINC' ) || die();

/**
 * Constants.
 */
const EXPORT_ACTION = 'Export to CSV';

/**
 * Actions and filters.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\add_subpage' );
add_action( 'admin_init', __NAMESPACE__ . '\handle_csv_export' );

/**
 * Register an admin page under Block Patterns for viewing stats.
 *
 * @return void
 */
function add_subpage() {
	$parent_slug = add_query_arg( 'post_type', PATTERN_POST_TYPE, 'edit.php' );

	$post_type_object = get_post_type_object( PATTERN_POST_TYPE );

	add_submenu_page(
		$parent_slug,
		__( 'Pattern Stats', 'wporg-patterns' ),
		__( 'Stats', 'wporg-patterns' ),
		$post_type_object->cap->edit_posts,
		PATTERN_POST_TYPE . '-stats',
		__NAMESPACE__ . '\render_subpage'
	);
}

/**
 * Render the stats subpage.
 *
 * @return void
 */
function render_subpage() {
	$schema        = get_meta_field_schema();
	$current_data  = get_snapshot_data();
	$snapshot_info = get_snapshot_meta_data();
	$next_snapshot = wp_get_scheduled_event( PATTERN_POST_TYPE . '_record_snapshot' );
	$inputs        = get_export_form_inputs();
	$export_label  = EXPORT_ACTION;

	require dirname( __DIR__ ) . '/views/admin-stats.php';
}

/**
 * Get meta data about existing snapshots.
 *
 * @return array
 */
function get_snapshot_meta_data() {
	$earliest_snapshot     = get_snapshots( array(
		'order'       => 'asc',
		'numberposts' => 1,
	) );
	$latest_snapshot_query = get_snapshots(
		array(
			'order'       => 'desc',
			'numberposts' => 1,
		),
		true
	);

	$total_snapshots = $latest_snapshot_query->found_posts;
	$earliest_date   = '';
	$latest_date     = '';

	if ( $total_snapshots > 0 ) {
		$latest_snapshot = $latest_snapshot_query->get_posts();
		$earliest_date   = get_the_date( 'Y-m-d', reset( $earliest_snapshot ) );
		$latest_date     = get_the_date( 'Y-m-d', reset( $latest_snapshot ) );
	}

	return array(
		'total_snapshots' => $total_snapshots,
		'earliest_date'   => $earliest_date,
		'latest_date'     => $latest_date,
	);
}

/**
 * Collect and validate the export form inputs.
 *
 * @return array
 */
function get_export_form_inputs() {
	$date_filter = function( $string ) {
		$success = preg_match( '|([0-9]{4}\-[0-9]{2}\-[0-9]{2})|', $string, $match );

		if ( $success ) {
			return $match[1];
		}

		return '';
	};

	$input_config = array(
		'start'    => array(
			'filter'  => FILTER_CALLBACK,
			'options' => $date_filter,
		),
		'end'      => array(
			'filter'  => FILTER_CALLBACK,
			'options' => $date_filter,
		),
		'action'   => FILTER_DEFAULT,
		'_wpnonce' => FILTER_DEFAULT,
	);

	$defaults = array_fill_keys( array_keys( $input_config ), '' );
	$inputs   = filter_input_array( INPUT_POST, $input_config );

	return wp_parse_args( $inputs, $defaults );
}

/**
 * Process an export form submission.
 *
 * @return void
 */
function handle_csv_export() {
	require_once __DIR__ . '/class-export-csv.php';
	$csv = new \WordCamp\Utilities\Export_CSV();

	$action = EXPORT_ACTION;
	$cpt    = get_post_type_object( PATTERN_POST_TYPE );
	$info   = get_snapshot_meta_data();
	$inputs = get_export_form_inputs();
	$schema = get_meta_field_schema();

	if ( $action !== $inputs['action'] ) {
		return;
	}

	if ( ! current_user_can( $cpt->cap->edit_posts ) ) {
		$csv->error->add( 'no_permission', 'Sorry, you do not have permission to do this.' );
		$csv->emit_file();
	}

	if ( ! wp_verify_nonce( $inputs['_wpnonce'], EXPORT_ACTION ) ) {
		$csv->error->add( 'invalid_nonce', 'Nonce failed. Try refreshing the screen.' );
		$csv->emit_file();
	}

	try {
		$start_date = new \DateTime( $inputs['start'] );
		$end_date   = new \DateTime( $inputs['end'] );
		$earliest   = new \DateTime( $info['earliest_date'] );
		$latest     = new \DateTime( $info['latest_date'] );
	} catch ( \Exception $exception ) {
		$csv->error->add(
			'invalid_date',
			$exception->getMessage()
		);
		$csv->emit_file();
	}

	$csv->set_filename( array(
		'patterns-snapshots',
		$start_date->format( 'Ymd' ),
		$end_date->format( 'Ymd' ),
	) );

	$csv->set_column_headers( array_merge(
		array( 'Date' ),
		array_keys( $schema['properties'] )
	) );

	if ( $start_date < $earliest ) {
		$csv->error->add(
			'invalid_date',
			sprintf(
				'Date range must begin %s or later.',
				$earliest->format( 'Y-m-d' )
			)
		);
		$csv->emit_file();
	}

	if ( $end_date > $latest ) {
		$csv->error->add(
			'invalid_date',
			sprintf(
				'Date range must end %s or earlier.',
				$latest->format( 'Y-m-d' )
			)
		);
		$csv->emit_file();
	}

	if ( $start_date > $end_date ) {
		$csv->error->add(
			'invalid_date',
			'Date range start must be less than or equal to date range end.'
		);
		$csv->emit_file();
	}

	$query_args = array(
		'order' => 'asc',
		'posts_per_page' => -1,
		'date_query' => array(
			array(
				'after'     => $start_date->format( 'Y-m-d' ),
				'before'    => $end_date->format( 'Y-m-d' ),
				'inclusive' => true,
			),
		),
	);

	$snapshots = get_snapshots( $query_args );

	if ( ! $snapshots ) {
		$csv->error->add(
			'no_data',
			'No snapshots were found.'
		);
		$csv->emit_file();
	}

	$data = array_map(
		function( $snapshot ) use ( $schema ) {
			$date = get_the_date( 'Y-m-d', $snapshot );
			$row  = array( $date );

			foreach ( array_keys( $schema['properties'] ) as $key ) {
				$row[] = $snapshot->$key;
			}

			return $row;
		},
		$snapshots
	);

	$csv->add_data_rows( $data );

	$csv->emit_file();
}
