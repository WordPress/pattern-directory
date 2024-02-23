<?php

namespace WordPressdotorg\Theme\Pattern_Directory_2024;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

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
 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
 * @param WP_Block $block Block instance.
 * @param int      $page  Current query's page.
 */
function update_query_loop_vars( $query, $block, $page ) {
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

	return $query;
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
