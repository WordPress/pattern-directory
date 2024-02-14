<?php

namespace WordPressdotorg\Theme\Pattern_Directory_2022;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use function WordPressdotorg\Theme\Pattern_Directory_2022\Order_Dropdown_Block\get_orderby_args;
use function WordPressdotorg\Pattern_Directory\Favorite\{get_favorites};

// Block files
require_once( __DIR__ . '/src/blocks/copy-button/index.php' );
require_once( __DIR__ . '/src/blocks/favorite-button/index.php' );
require_once( __DIR__ . '/src/blocks/order-dropdown/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-preview/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-thumbnail/index.php' );

require_once( __DIR__ . '/inc/block-config.php' );

/**
 * Actions and filters.
 */
add_action( 'after_setup_theme', __NAMESPACE__ . '\theme_support', 9 );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\update_query_loop_vars', 10, 3 );
add_action( 'pre_get_posts', __NAMESPACE__ . '\pre_get_posts' );
add_filter( 'wp_get_nav_menu_items', __NAMESPACE__ . '\contextualize_category_menu_links', 10, 3 );

add_action(
	'init',
	function() {
		// Don't swap author link with w.org profile link.
		remove_all_filters( 'author_link' );
	}
);

/**
 * Register theme support.
 */
function theme_support() {
	// Add the category menu.
	register_nav_menus(
		array(
			'category-filters' => __( 'Category Filters', 'wporg' ),
			'status-filters' => __( 'Status Filters', 'wporg' ),
		)
	);
}

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
 * Filter the query loop arguments.
 *
 * Used when listing patterns on pages, ex Favorites (query.inherit = false).
 *
 * @param array    $query Array containing parameters for `WP_Query` as parsed by the block context.
 * @param WP_Block $block Block instance.
 * @param int      $page  Current query's page.
 */
function update_query_loop_vars( $query, $block, $page ) {
	if ( ! isset( $query['posts_per_page'] ) ) {
		$query['posts_per_page'] = 18;
	}

	if ( isset( $query['post_type'] ) && 'wporg-pattern' === $query['post_type'] ) {
		// This is used for the "more by this designer" section.
		// The `[current]` author is a placeholder for the current post's author, and in this case
		// we also want to exclude the current post from results.
		$current_post = get_post();
		if ( $current_post && isset( $query['author'] ) && '[current]' === $query['author'] ) {
			$query['author'] = $current_post->post_author;
			$query['post__not_in'] = [ $current_post->ID ];
		}
	}

	if ( isset( $page ) && ! isset( $query['offset'] ) ) {
		$query['paged'] = $page;
	}

	if ( is_page( [ 'my-patterns', 'favorites' ] ) ) {
		if ( isset( $_GET['_orderby'] ) ) {
			$args = get_orderby_args( $_GET['_orderby'] );
			$query = array_merge( $query, $args );
		}

		if ( isset( $_GET['wporg-pattern-category'] ) 
			&& term_exists( $_GET['wporg-pattern-category'], 'wporg-pattern-category' )
		) {
			$query['wporg-pattern-category'] = $_GET['wporg-pattern-category'];
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
		$query['post__in'] = get_favorites();
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
		$query->set( 'posts_per_page', 18 );
		$query->set( 'post_type', array( POST_TYPE ) );

		if ( isset( $_GET['_orderby'] ) ) {
			$args = get_orderby_args( $_GET['_orderby'] );
			foreach( $args as $key => $value ) {
				$query->set( $key, $value );
			}
		}

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
 * Adjust the URLs for category filtering on other pages, ex author archives, favorites.
 *
 * @param array  $items An array of menu item post objects.
 * @param object $menu  The menu object.
 * @param array  $args  An array of arguments used to retrieve menu item objects.
 *
 * @return array Updated menu items.
 */
function contextualize_category_menu_links( $items, $menu, $args ) {
	global $wp;
	$locations = get_nav_menu_locations();
	$category_menu_id = $locations['category-filters'];
	if ( $menu->term_id === $category_menu_id ) {
		foreach ( $items as $item ) {
			$path = $wp->request ?? '/';
			$path = trailingslashit( preg_replace( '/\/page\/[\d]+/', '', $path ) );
			$url = home_url( $path );
			$term = get_term( $item->object_id, $item->object );
			if ( ! is_wp_error( $term ) ) {
				if ( is_search() ) {
					$search_term = get_search_query();
					$item->url = add_query_arg(
						array(
							'wporg-pattern-category' => $term->slug,
							's' => $search_term,
						),
						$url
					);
				} else if ( ! is_home() && ! is_tax( 'wporg-pattern-category' ) ) {
					$item->url = add_query_arg( 'wporg-pattern-category', $term->slug, $url );
				}
			} else {
				if ( is_search() ) {
					$search_term = get_search_query();
					$item->url = add_query_arg( 's', $search_term, $url );
				} else if ( ! is_home() && ! is_tax( 'wporg-pattern-category' ) ) {
					$item->url = $url;
				}
			}
		}
	}
	return $items;
}
