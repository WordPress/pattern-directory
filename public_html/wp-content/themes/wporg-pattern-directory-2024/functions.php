<?php

namespace WordPressdotorg\Theme\Pattern_Directory_2024;

use function WordPressdotorg\Pattern_Directory\Favorite\{get_favorites, get_favorite_count};
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

// Block files
require_once( __DIR__ . '/src/blocks/copy-button/index.php' );
require_once( __DIR__ . '/src/blocks/delete-button/index.php' );
require_once( __DIR__ . '/src/blocks/favorite-button/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-preview/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-thumbnail/index.php' );
require_once( __DIR__ . '/src/blocks/post-status/index.php' );
require_once( __DIR__ . '/src/blocks/status-notice/index.php' );

require_once( __DIR__ . '/inc/block-config.php' );
require_once( __DIR__ . '/inc/shortcodes.php' );

/**
 * Actions and filters.
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
add_action( 'template_redirect', __NAMESPACE__ . '\do_pattern_actions' );
add_action( 'query_vars', __NAMESPACE__ . '\add_patterns_query_vars' );
add_action( 'pre_get_posts', __NAMESPACE__ . '\modify_patterns_query' );
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\modify_query_loop_block_query_vars', 10, 3 );
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\custom_query_loop_by_id', 20, 2 );

add_action(
	'init',
	function() {
		// Don't swap author link with w.org profile link.
		remove_all_filters( 'author_link' );

		// Remove the "By…" from the author name block.
		remove_filter( 'render_block_core/post-author-name', 'WordPressdotorg\Theme\Parent_2021\Gutenberg_Tweaks\render_author_prefix', 10, 2 );
	}
);

/**
 * Enqueue scripts and styles.
 */
function enqueue_assets() {
	// The parent style is registered as `wporg-parent-2021-style`, and will be loaded unless
	// explicitly unregistered. We can load any child-theme overrides by declaring the parent
	// stylesheet as a dependency.
	wp_enqueue_style(
		'wporg-pattern-directory-2024-style',
		get_stylesheet_uri(),
		array( 'wporg-parent-2021-style', 'wporg-global-fonts' ),
		filemtime( __DIR__ . '/style.css' )
	);
}

/**
 * Check if the current request needs an action, and run that action.
 *
 * Available actions:
 * - draft: Update the current post to a draft.
 */
function do_pattern_actions() {
	if ( ! is_singular( POST_TYPE ) ) {
		return;
	}

	$action = isset( $_GET['action'] ) ? $_GET['action'] : false;
	$nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : false;
	$post_id = get_the_ID();

	if ( 'draft' === $action ) {
		if ( wp_verify_nonce( $nonce, 'draft-' . $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			// Draft the post.
			$success = wp_update_post(
				array(
					'ID' => $post_id,
					'post_status' => 'draft',
				)
			);
			if ( $success ) {
				// Reload the page without the action.
				wp_safe_redirect( get_the_permalink() );
			} else {
				// Reload the page with an error flag.
				$url = add_query_arg(
					array(
						'error' => 'draft-failed'
					),
					get_the_permalink()
				);
				wp_safe_redirect( $url );
			}
		}
	}
}

/**
 * Add custom query parameters.
 *
 * @param array $query_vars
 *
 * @return array
 */
function add_patterns_query_vars( $query_vars ) {
	$query_vars[] = 'curation';
	$query_vars[] = 'status';
	return $query_vars;
}

/**
 * Update the query to show patters according to the "curation" &
 * sort order filters.
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 */
function modify_patterns_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// If `curation` is passed and either `core` or `community`, we should
	// filter the result. If `curation=all`, no filtering is needed.
	$curation = $query->get( 'curation' );
	if ( $curation ) {
		$tax_query = isset( $query->tax_query->queries ) ? $query->tax_query->queries : [];
		if ( 'core' === $curation ) {
			// Patterns with the core keyword.
			$tax_query['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => [ 'core' ],
				'operator' => 'IN',
			);
		} else if ( 'community' === $curation ) {
			// Patterns without the core keyword.
			$tax_query['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => [ 'core' ],
				'operator' => 'NOT IN',
			);
		}
		$query->set( 'tax_query', $tax_query );
	}

	if ( str_ends_with( $query->get( 'orderby' ), '_desc' ) ) {
		$orderby = str_replace( '_desc', '', $query->get( 'orderby' ) );
		$query->set( 'orderby', $orderby );
		$query->set( 'order', 'desc' );
	} else if ( str_ends_with( $query->get( 'orderby' ), '_asc' ) ) {
		$orderby = str_replace( '_asc', '', $query->get( 'orderby' ) );
		$query->set( 'orderby', $orderby );
		$query->set( 'order', 'asc' );
	}

	if ( $query->get( 'orderby' ) === 'favorite_count' ) {
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'meta_key', 'wporg-pattern-favorites' );
	}

	if ( ! $query->is_singular() ) {
		$query->set( 'post_type', array( POST_TYPE ) );

		// The `orderby_locale` meta_query will be transformed into a query orderby by Pattern_Post_Type\filter_orderby_locale().
		$query->set( 'meta_query', array(
			'orderby_locale' => array(
				'key'     => 'wpop_locale',
				'compare' => 'IN',
				// Order in value determines result order
				'value'   => array( get_locale(), 'en_US' ),
			),
		) );
	}
}

/**
 * Set up query customizations for the Query Loop block.
 *
 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
 * @param WP_Block $block Block instance.
 * @param int      $page  Current query's page.
 *
 * @return array
 */
function modify_query_loop_block_query_vars( $query, $block, $page ) {
	global $wp_query;

	// Return early if this is a pattern view page.
	if ( isset( $wp_query->query_vars['view'] ) ) {
		return $query;
	}

	if ( ! isset( $query['posts_per_page'] ) ) {
		$query['posts_per_page'] = 24;
	}

	if ( isset( $page ) && ! isset( $query['offset'] ) ) {
		$query['paged'] = $page;
	}

	if ( isset( $block->context['query']['curation'] ) ) {
		if ( 'core' === $block->context['query']['curation'] ) {
			// Patterns with the core keyword.
			$query['tax_query']['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => 'core',
				'operator' => 'IN',
			);
		} else if ( 'community' === $block->context['query']['curation'] ) {
			// Patterns without the core keyword.
			$query['tax_query']['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => [ 'core' ],
				'operator' => 'NOT IN',
			);
		}
	}

	if ( isset( $block->context['query']['orderBy'] ) && 'favorite_count' === $block->context['query']['orderBy'] ) {
		$query['orderby'] = 'meta_value_num';
		$query['meta_key'] = 'wporg-pattern-favorites';
	}

	// Query Loops on My Patterns & Favorites pages
	if ( is_page( [ 'my-patterns', 'favorites' ] ) ) {
		// Get these values from the global wp_query, they're passed via the URL.
		if ( isset( $wp_query->query['pattern-categories'] ) ) {
			if ( ! isset( $query['tax_query'] ) || ! is_array( $query['tax_query'] ) ) {
				$query['tax_query'] = array();
			}
			$query['tax_query'][] = array(
				'taxonomy'         => 'wporg-pattern-category',
				'field'            => 'slug',
				'terms'            => $wp_query->query['pattern-categories'],
				'include_children' => false,
			);
		}

		if ( isset( $wp_query->query['orderby'] ) ) {
			if ( str_ends_with( $wp_query->query['orderby'], '_desc' ) ) {
				$orderby = str_replace( '_desc', '', $wp_query->query['orderby'] );
				$query['orderby'] = $orderby;
				$query['order'] = 'desc';
			} else if ( str_ends_with( $wp_query->query['orderby'], '_asc' ) ) {
				$orderby = str_replace( '_asc', '', $wp_query->query['orderby'] );
				$query['orderby'] = $orderby;
				$query['order'] = 'asc';
			}
		}

		if ( is_page( 'my-patterns' ) ) {
			$user_id = get_current_user_id();
			if ( $user_id ) {
				$query['post_type'] = 'wporg-pattern';
				$query['post_status'] = 'any';
				$query['author'] = get_current_user_id();
			} else {
				$query['post__in'] = [ -1 ];
			}

			if ( isset( $wp_query->query['status'] ) ) {
				$query['post_status'] = $wp_query->query['status'];
			}
		}

		if ( is_page( 'favorites' ) ) {
			$favorites = get_favorites();
			if ( ! empty( $favorites ) ) {
				$query['post__in'] = get_favorites();
			} else {
				$query['post__in'] = [ -1 ];
			}
		}
	}

	// The `orderby_locale` meta_query will be transformed into a query orderby by Pattern_Post_Type\filter_orderby_locale().
	$query['meta_query'] = array(
		'orderby_locale' => array(
			'key'     => 'wpop_locale',
			'compare' => 'IN',
			// Order in value determines result order
			'value'   => array( get_locale(), 'en_US' ),
		),
	);

	return $query;
}

/**
 * Override Query Loop parameters if an `_id` property is found.
 *
 * This is a workaround to allow setting more complicated queries. For example,
 * using the current author & excluding the current post.
 *
 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
 * @param WP_Block $block Block instance.
 *
 * @return array
 */
function custom_query_loop_by_id( $query, $block ) {
	if ( ! isset( $block->context['query']['_id'] ) ) {
		return $query;
	}

	$current_post = get_post();
	if ( 'more-by-author' === $block->context['query']['_id'] && $current_post && $current_post->post_author ) {
		$query['author'] = $current_post->post_author;
		$query['post__not_in'] = [ $current_post->ID ];
		$query['post_type'] = 'wporg-pattern';
	}

	if ( 'empty-favorites' === $block->context['query']['_id'] ) {
		unset( $query['post__in'] );
		$query['post_type'] = 'wporg-pattern';
		$query['orderby'] = 'meta_value_num';
		$query['meta_key'] = 'wporg-pattern-favorites';
	}

	return $query;
}

/**
 * Get the preview URL for the current pattern.
 *
 * @param int|WP_Post $post Post ID or post object.
 *
 * @return string The pattern `view` URL.
 */
function get_pattern_preview_url( $post = 0 ) {
	$view_url = add_query_arg( 'view', true, get_permalink( $post ) );
	return apply_filters( 'wporg_pattern_preview_url', $view_url, $post );
}
