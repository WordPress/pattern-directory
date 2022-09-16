<?php

namespace WordPressdotorg\Theme\Pattern_Directory_2022;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

// Block files
require_once( __DIR__ . '/src/pattern-thumbnail/index.php' );
require_once( __DIR__ . '/src/categories-list/index.php' );

/**
 * Actions and filters.
 */
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\update_query_loop_vars', 10, 3 );
add_action( 'pre_get_posts', __NAMESPACE__ . '\pre_get_posts' );

/**
 * Enqueue scripts and styles.
 */
function enqueue_assets() {
	// The parent style is registered as `wporg-parent-2021-style`, and will be loaded unless
	// explicitly unregistered. We can load any child-theme overrides by declaring the parent
	// stylesheet as a dependency.
	wp_enqueue_style(
		'wporg-pattern-directory-2022-style',
		get_stylesheet_uri(),
		array( 'wporg-parent-2021-style', 'wporg-global-fonts' ),
		filemtime( __DIR__ . '/style.css' )
	);
}

/**
 *
 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
 * @param WP_Block $block Block instance.
 * @param int      $page  Current query's page.
 */
function update_query_loop_vars( $query, $block, $page ) {
	if ( isset( $query['post_type']  ) && 'wporg-pattern' === $query['post_type'] ) {
		if ( ! isset( $query['posts_per_page'] ) ) {
			$query['posts_per_page'] = 18;
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

		// This is used for the "more by this designer" section.
		// The `[current]` author is a placeholder for the current post's author, and in this case
		// we also want to exclude the current post from results.
		$current_post = get_post();
		if ( $current_post && isset( $query['author'] ) && '[current]' === $query['author'] ) {
			$query['author'] = $current_post->post_author;
			$query['post__not_in'] = [ $current_post->ID ];
		}
	}

	return $query;
}

/**
 *
 * @param \WP_Query $query The WordPress Query object.
 */
function pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_tax( 'wporg-pattern-category' ) || $query->is_search() ) {
		$query->set( 'posts_per_page', 18 );

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
