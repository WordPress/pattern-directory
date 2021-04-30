<?php

namespace WordPressdotorg\Pattern_Directory\Admin\Patterns;

use WP_Post, WP_Query;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\POST_TYPE as FLAG;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_REASON;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\PENDING_STATUS;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\RESOLVED_STATUS;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'manage_' . PATTERN . '_posts_columns', __NAMESPACE__ . '\pattern_list_table_columns' );
add_action( 'manage_' . PATTERN . '_posts_custom_column', __NAMESPACE__ . '\pattern_list_table_render_custom_columns', 10, 2 );
add_action( 'manage_posts_extra_tablenav', __NAMESPACE__ . '\pattern_list_table_styles' );

/**
 * Modify the patterns list table columns.
 *
 * @param array $columns
 *
 * @return array
 */
function pattern_list_table_columns( $columns ) {
	$flag = get_post_type_object( FLAG );

	$flags_html = sprintf(
		'<span class="vers dashicons dashicons-warning" title="%s"><span class="screen-reader-text">%s</span></span>',
		esc_attr( $flag->labels->all_items ),
		esc_html( $flag->labels->all_items )
	);

	$columns = array_slice( $columns, 0, 3, true )
				+ array(
					'flags' => esc_html__( 'Pending Flags', 'wporg-patterns' ),
				)
				+ array_slice( $columns, 3, null, true );

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
function pattern_list_table_render_custom_columns( $column_name, $post_id ) {
	$current_pattern = get_post( $post_id );

	switch ( $column_name ) {
		case 'flags':
			$flags = new WP_Query( array(
				'post_type'   => FLAG,
				'post_status' => array( 'pending' ),
				'post_parent' => $post_id,
			) );

			if ( $flags->found_posts > 0 ) {
				$url = add_query_arg(
					array(
						'post_type'   => FLAG,
						'post_parent' => $post_id,
						'post_status' => 'pending',
					),
					admin_url( 'edit.php' )
				);

				printf(
					'<a href="%s" class="flag-count">
						<span class="flag-count-bubble" aria-hidden="true">%s</span>
						<span class="screen-reader-text">%s</span>
					</a>',
					esc_attr( $url ),
					esc_html( number_format_i18n( $flags->found_posts ) ),
					sprintf(
						esc_html( _n(
							'%s pending flag',
							'%s pending flags',
							$flags->found_posts,
							'wporg-patterns'
						) ),
						esc_html( number_format_i18n( $flags->found_posts ) )
					)
				);
			} else {
				echo '&mdash;';
			}
			break;
	}
}

/**
 * Add some styles for the patterns list table.
 *
 * @param string $which
 *
 * @return void
 */
function pattern_list_table_styles( $which ) {
	global $typenow;

	if ( PATTERN !== $typenow || 'bottom' !== $which ) {
		return;
	}

	?>
	<style>
		.flag-count {
			display: inline-block;
			padding: 0 5px;
			min-width: 7px;
			height: 17px;
			border-radius: 11px;
			background: #d63638;
			color: #fff;
			font-size: 9px;
			line-height: 1.88888888;
			text-align: center;
		}

		.flag-count-bubble {
			color: #fff;
			font-size: 9px;
			line-height: 1.88888888;
			text-align: center;
		}
	</style>
	<?php
}
