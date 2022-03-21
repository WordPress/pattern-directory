<?php

namespace WordPressdotorg\Pattern_Directory\Admin\Patterns;

use WP_Post, WP_Query;
use function WordPressdotorg\Locales\get_locales_with_english_names;
use function WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\get_pattern_ids_with_pending_flags;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\POST_TYPE as FLAG;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_REASON;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\PENDING_STATUS;
use const  WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ UNLISTED_STATUS, SPAM_STATUS };

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'manage_' . PATTERN . '_posts_columns', __NAMESPACE__ . '\pattern_list_table_columns' );
add_action( 'manage_' . PATTERN . '_posts_custom_column', __NAMESPACE__ . '\pattern_list_table_render_custom_columns', 10, 2 );
add_action( 'manage_posts_extra_tablenav', __NAMESPACE__ . '\pattern_list_table_styles' );
add_filter( 'views_edit-' . PATTERN, __NAMESPACE__ . '\pattern_list_table_views' );
add_action( 'pre_get_posts', __NAMESPACE__ . '\handle_pattern_list_table_views' );
add_filter( 'display_post_states', __NAMESPACE__ . '\display_post_states', 10, 2 );
add_filter( 'post_row_actions', __NAMESPACE__ . '\add_row_actions', 10, 2 );
add_filter( 'bulk_actions-edit-' . PATTERN, __NAMESPACE__ . '\add_bulk_actions' );
add_filter( 'handle_bulk_actions-edit-' . PATTERN, __NAMESPACE__ . '\handle_bulk_actions', 10, 3 );

/**
 * Modify the patterns list table columns.
 *
 * @param array $columns
 *
 * @return array
 */
function pattern_list_table_columns( $columns ) {
	$flag = get_post_type_object( FLAG );

	$columns = array_slice( $columns, 0, 3, true )
				+ array(
					'flags'    => esc_html__( 'Pending Flags', 'wporg-patterns' ),
					'language' => esc_html__( 'Language', 'wporg-patterns' ),
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
				'post_parent' => $current_pattern->ID,
				'numberposts' => 1,
			) );

			if ( $flags->found_posts > 0 ) {
				$url = add_query_arg(
					array(
						'post_type'   => FLAG,
						'post_parent' => $current_pattern->ID,
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

		case 'language':
			$locale        = $current_pattern->wpop_locale ?: 'en_US';
			$locale_labels = get_locales_with_english_names();

			if ( isset( $locale_labels[ $locale ] ) ) {
				printf(
					'<span class="language-label">%s</span>',
					esc_html( $locale_labels[ $locale ] )
				);
			} else {
				echo '&mdash;';
			}

			if ( $current_pattern->wpop_is_translation ) {
				$parent = get_post( $current_pattern->post_parent );

				if ( $parent ) {
					printf(
						'<span class="language-context">%s</span>',
						esc_html__( 'Translation', 'wporg-patterns' )
					);

					$view_url = add_query_arg(
						array(
							'post_type' => PATTERN,
							'post_id'   => $parent->ID,
						),
						admin_url( 'edit.php' )
					);

					printf(
						'<a href="%1$s" class="language-context-link">%2$s</a>',
						esc_url( $view_url ),
						esc_html__( 'Show original', 'wporg-patterns' )
					);
				}
			} else {
				printf(
					'<span class="language-context">%s</span>',
					esc_html__( 'Original', 'wporg-patterns' )
				);

				$args = array(
					'post_type'    => PATTERN,
					'post_status'  => 'any',
					'post_parent'  => $current_pattern->ID,
					'number_posts' => 1,
					'meta_query'   => array(
						array(
							'key'   => 'wpop_is_translation',
							'value' => 1,
						),
					),
				);

				$translations = new WP_Query( $args );
				$view_url     = add_query_arg(
					array(
						'post_type'      => PATTERN,
						'is_translation' => true,
						'post_parent'    => $current_pattern->ID,
					),
					admin_url( 'edit.php' )
				);

				printf(
					'<a href="%1$s" class="language-context-link">%2$s</a>',
					esc_url( $view_url ),
					sprintf(
						esc_html( _n(
							'%s translation',
							'%s translations',
							$translations->found_posts,
							'wporg-patterns'
						) ),
						esc_html( number_format_i18n( $translations->found_posts ) )
					)
				);
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

		td.column-language {
			display: flex;
			flex-direction: column;
		}

		.language-label {
			display: block;
			margin-bottom: 0.5em;
		}

		.language-context,
		.language-context-link {
			font-size: 0.85em;
			line-height: 1.3;
		}

		.row-actions span.spam a,
		.row-actions span.unlist a {
			color: #bd8600;
		}

		.row-actions span.spam a:hover,
		.row-actions span.unlist a:hover {
			color: #996800;
		}
	</style>
	<?php
}

/**
 * Add view links to the patterns list table.
 *
 * @param array $views
 *
 * @return array
 */
function pattern_list_table_views( $views ) {
	$wants_flagged   = filter_input( INPUT_GET, 'has_flags', FILTER_VALIDATE_BOOLEAN );
	$wants_originals = 0 === filter_input( INPUT_GET, 'post_parent', FILTER_VALIDATE_INT );

	$url = add_query_arg(
		array(
			'post_type' => PATTERN,
			'has_flags' => 1,
		),
		admin_url( 'edit.php' )
	);

	$extra_attributes = '';
	if ( $wants_flagged ) {
		$extra_attributes = ' class="current" aria-current="page"';
	}

	$patterns_with_flags = get_pattern_ids_with_pending_flags();

	$views['has_flags'] = sprintf(
		'<a href="%s"%s>%s</a>',
		esc_url( $url ),
		$extra_attributes,
		sprintf(
			/* translators: %s: Number of posts. */
			_n(
				'Has Flags <span class="count">(%s)</span>',
				'Have Flags <span class="count">(%s)</span>',
				count( $patterns_with_flags ),
				'wporg-patterns'
			),
			number_format_i18n( count( $patterns_with_flags ) )
		)
	);

	$url = add_query_arg(
		array(
			'post_type'   => PATTERN,
			'post_parent' => 0,
		),
		admin_url( 'edit.php' )
	);

	$extra_attributes = '';
	if ( $wants_originals ) {
		$extra_attributes = ' class="current" aria-current="page"';
	}

	$args = array(
		'post_type'   => PATTERN,
		'post_status' => array( 'draft', 'pending', 'publish' ),
		'post_parent' => 0,
		'numberposts' => 1,
	);
	$query = new WP_Query( $args );

	$views['originals'] = sprintf(
		'<a href="%s"%s>%s</a>',
		esc_url( $url ),
		$extra_attributes,
		sprintf(
			/* translators: %s: Number of posts. */
			_n(
				'Original <span class="count">(%s)</span>',
				'Originals <span class="count">(%s)</span>',
				$query->found_posts,
				'wporg-patterns'
			),
			number_format_i18n( $query->found_posts )
		)
	);

	return $views;
}

/**
 * Modify the query that populates the patterns list table.
 *
 * @param WP_Query $query
 *
 * @return void
 */
function handle_pattern_list_table_views( WP_Query $query ) {
	$wants_flagged      = filter_input( INPUT_GET, 'has_flags', FILTER_VALIDATE_BOOLEAN );
	$wants_translations = filter_input( INPUT_GET, 'is_translation', FILTER_VALIDATE_BOOLEAN );
	$post_id            = filter_input( INPUT_GET, 'post_id', FILTER_VALIDATE_INT );
	$post_parent        = filter_input( INPUT_GET, 'post_parent', FILTER_VALIDATE_INT );

	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$current_screen = get_current_screen();

	if ( 'edit-' . PATTERN === $current_screen->id ) {
		if ( $wants_flagged ) {
			$args = array(
				'orderby' => $query->get( 'orderby', 'date' ),
				'order'   => $query->get( 'order', 'desc' ),
			);

			$valid_ids = get_pattern_ids_with_pending_flags( $args );

			if ( empty( $valid_ids ) ) {
				$valid_ids = array( 0 );
			}

			$query->set( 'post__in', $valid_ids );
		}

		if ( $wants_translations ) {
			$meta_query = $query->get( 'meta_query', array() );
			$meta_query[] = array(
				'key'   => 'wpop_is_translation',
				'value' => 1,
			);
			$query->set( 'meta_query', $meta_query );
		}

		if ( false !== $post_id ) {
			$query->set( 'p', $post_id );
		}

		if ( false !== $post_parent ) {
			$query->set( 'post_parent', $post_parent );
		}
	}
}

/**
 * More post states for the Patterns list table.
 *
 * @param array   $post_states
 * @param WP_Post $post
 *
 * @return array
 */
function display_post_states( $post_states, $post ) {
	if ( isset( $_REQUEST['post_status'] ) ) {
		$post_status = $_REQUEST['post_status'];
	} else {
		$post_status = '';
	}

	if (
		$post->post_status !== $post_status &&
		in_array( $post->post_status, [ UNLISTED_STATUS, SPAM_STATUS ] )
	) {
		$post_states[ $post->post_status ] = get_post_status_object( $post->post_status )->label;
	}

	return $post_states;
}

/**
 * Set up row actions for patterns list table.
 *
 * Adds action links for "Publish", "Spam", and "Unlist".
 *
 * @param string[] $actions An array of row action links.
 * @param WP_Post  $post    The post object.
 *
 * @return array Filtered actions.
 */
function add_row_actions( $actions, $post ) {
	if ( PATTERN !== $post->post_type ) {
		return $actions;
	}

	$saved_actions = array_intersect_key( $actions, array_fill_keys( array( 'trash', 'untrash', 'delete' ), true ) );
	$actions       = array_intersect_key( $actions, array_fill_keys( array( 'edit', 'view' ), true ) );

	$edit_url = add_query_arg( 'post_type', PATTERN, 'edit.php' );
	$title = _draft_or_post_title();

	if ( PENDING_STATUS === $post->post_status || SPAM_STATUS === $post->post_status ) {
		$publish_url = add_query_arg(
			array(
				'action' => 'publish',
				'post'   => array( $post->ID ),
			),
			wp_nonce_url( $edit_url, 'bulk-posts' )
		);

		$actions['publish'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			$publish_url,
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'Publish &#8220;%s&#8221;', 'wporg-patterns' ), $title ) ),
			_x( 'Publish', 'verb', 'wporg-patterns' )
		);
	}

	if ( SPAM_STATUS === $post->post_status ) {
		$unlist_url = add_query_arg(
			array(
				'action' => 'unlist',
				'post'   => array( $post->ID ),
			),
			wp_nonce_url( $edit_url, 'bulk-posts' )
		);

		$actions['unlist'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			$unlist_url,
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'Remove &#8220;%s&#8221; from the directory', 'wporg-patterns' ), $title ) ),
			_x( 'Unlist', 'verb', 'wporg-patterns' )
		);
	}

	if ( SPAM_STATUS !== $post->post_status && UNLISTED_STATUS !== $post->post_status ) {
		$spam_url = add_query_arg(
			array(
				'action' => 'spam',
				'post'   => array( $post->ID ),
			),
			wp_nonce_url( $edit_url, 'bulk-posts' )
		);

		$actions['spam'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			$spam_url,
			/* translators: %s: Post title. */
			esc_attr( sprintf( __( 'Mark &#8220;%s&#8221; as spam', 'wporg-patterns' ), $title ) ),
			_x( 'Spam', 'verb', 'wporg-patterns' )
		);
	}

	return $actions + $saved_actions;
}

/**
 * Define bulk actions for the pattern list table.
 *
 * @param array $actions
 *
 * @return array
 */
function add_bulk_actions( $actions ) {
	$saved_actions = array_intersect_key( $actions, array_fill_keys( array( 'trash', 'untrash', 'delete' ), true ) );

	$actions = array(
		'publish' => __( 'Publish', 'wporg-patterns' ),
		'spam'    => __( 'Spam', 'wporg-patterns' ),
		'unlist'  => __( 'Unlist', 'wporg-patterns' ),
	);

	return $actions + $saved_actions;
}

/**
 * Execute bulk actions for the patterns list table.
 *
 * @param string $sendback
 * @param string $doaction
 * @param array  $post_ids
 *
 * @return mixed|string
 */
function handle_bulk_actions( $sendback, $doaction, $post_ids ) {
	$post_data = array(
		'post_type' => PATTERN,
		'post'      => $post_ids,
	);

	switch ( $doaction ) {
		case 'publish':
			$post_data['_status'] = 'publish';
			break;
		case 'spam':
			$post_data['_status'] = SPAM_STATUS;
			break;
		case 'unlist':
			$post_data['_status'] = UNLISTED_STATUS;

			$reason_term = get_term_by( 'slug', '4-spam', FLAG_REASON );
			if ( $reason_term ) {
				$post_data['tax_input'] = array(
					FLAG_REASON => array( $reason_term->term_id ),
				);
			}
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
