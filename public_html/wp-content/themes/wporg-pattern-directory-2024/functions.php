<?php

namespace WordPressdotorg\Theme\Pattern_Directory_2024;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use function WordPressdotorg\Pattern_Directory\Favorite\{get_favorites};

// Block files
require_once( __DIR__ . '/src/blocks/copy-button/index.php' );
require_once( __DIR__ . '/src/blocks/delete-button/index.php' );
require_once( __DIR__ . '/src/blocks/favorite-button/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-preview/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-thumbnail/index.php' );
require_once( __DIR__ . '/src/blocks/status-notice/index.php' );

require_once( __DIR__ . '/inc/block-config.php' );
require_once( __DIR__ . '/inc/shortcodes.php' );

/**
 * Actions and filters.
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\update_query_loop_vars', 10, 3 );
add_action( 'pre_get_posts', __NAMESPACE__ . '\pre_get_posts' );
add_action( 'template_redirect', __NAMESPACE__ . '\do_pattern_actions' );

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
 * Filter the query loop arguments.
 *
 * Used when listing patterns on pages, ex Favorites (query.inherit = false).
 *
 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
 * @param WP_Block $block Block instance.
 * @param int      $page  Current query's page.
 */
function update_query_loop_vars( $query, $block, $page ) {
	global $wp_query;

	if ( ! isset( $query['posts_per_page'] ) ) {
		$query['posts_per_page'] = 24;
	}

	if ( isset( $query['post_type'] ) && 'wporg-pattern' === $query['post_type'] ) {
		// This is used for the "more by this designer" section.
		// The `[current]` author is a placeholder for the current post's author, and in this case
		// we also want to exclude the current post from results.
		$current_post = get_post();
		if ( isset( $block->context['query']['override'] ) ) {
			if ( 'more-by-author' === $block->context['query']['override'] && $current_post && $current_post->post_author ) {
				$query['author'] = $current_post->post_author;
				$query['post__not_in'] = [ $current_post->ID ];
			}
		}
	}

	if ( isset( $page ) && ! isset( $query['offset'] ) ) {
		$query['paged'] = $page;
	}

	if ( is_page( [ 'my-patterns', 'favorites' ] ) ) {
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
				$orderby = str_replace( '_asc', '',$wp_query->query['orderby'] );
				$query['orderby'] = $orderby;
				$query['order'] = 'asc';
			}
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
	}

	if ( is_page( 'favorites' ) ) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$query['post__in'] = get_favorites();
		} else {
			$query['post__in'] = [ -1 ];
		}
	}

	return $query;
}

/**
 * Filter the default query.
 *
 * Used to render Patterns on archive pages (query.inherit = true).
 *
 * @param \WP_Query $query The WordPress Query object.
 */
function pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! $query->is_singular() ) {
		$query->set( 'posts_per_page', 24 );
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
