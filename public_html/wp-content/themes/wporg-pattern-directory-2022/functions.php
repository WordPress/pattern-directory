<?php

namespace WordPressdotorg\Theme\Pattern_Directory_2022;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use function WordPressdotorg\Theme\Pattern_Directory_2022\Order_Dropdown_Block\get_orderby_args;
use function WordPressdotorg\Pattern_Directory\Favorite\{get_favorites};
use WP_Block_Supports, WP_Block_Template;

// Block files
require_once( __DIR__ . '/src/blocks/copy-button/index.php' );
require_once( __DIR__ . '/src/blocks/favorite-button/index.php' );
require_once( __DIR__ . '/src/blocks/order-dropdown/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-preview/index.php' );
require_once( __DIR__ . '/src/blocks/pattern-thumbnail/index.php' );

/**
 * Actions and filters.
 */
add_action( 'after_setup_theme', __NAMESPACE__ . '\theme_support', 9 );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\update_query_loop_vars', 10, 3 );
add_action( 'pre_get_posts', __NAMESPACE__ . '\pre_get_posts' );
add_filter( 'wp_get_nav_menu_items', __NAMESPACE__ . '\contextualize_category_menu_links', 10, 3 );
add_action( 'render_block_core/template-part', __NAMESPACE__ . '\render_block_template_part_php_file', 10, 3 );
add_filter( 'pre_get_block_template', __NAMESPACE__ . '\get_block_template_php_file', 10, 3 );

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

/**
 * Update the template part block to render a PHP file.
 *
 * @param string   $block_content The block content.
 * @param array    $block         The full block, including name and attributes.
 * @param WP_Block $instance      The block instance.
 *
 * @return string Updated block content.
 */
function render_block_template_part_php_file( $block_content, $block, $instance ) {
	$file_path = get_template_part_php_file_path( $block['attrs']['slug'] );

	if ( ! $file_path || ! file_exists( $file_path ) ) {
		return $block_content;
	}

	$content = get_template_part_php_file_contents( $file_path );

	$area = WP_TEMPLATE_PART_AREA_UNCATEGORIZED;
	if ( empty( $attributes['tagName'] ) ) {
		$defined_areas = get_allowed_block_template_part_areas();
		$area_tag      = 'div';
		foreach ( $defined_areas as $defined_area ) {
			if ( $defined_area['area'] === $area && isset( $defined_area['area_tag'] ) ) {
				$area_tag = $defined_area['area_tag'];
			}
		}
		$html_tag = $area_tag;
	} else {
		$html_tag = esc_attr( $attributes['tagName'] );
	}

	// Required to prevent `block_to_render` from being null in `get_block_wrapper_attributes`.
	$parent = WP_Block_Supports::$block_to_render;
	WP_Block_Supports::$block_to_render = $block;
	$wrapper_attributes = get_block_wrapper_attributes();
	WP_Block_Supports::$block_to_render = $parent;
	return "<$html_tag $wrapper_attributes>" . str_replace( ']]>', ']]&gt;', $content ) . "</$html_tag>";
}

/**
 * Shortcut the template part request to load content from a PHP file, if it exists.
 *
 * This is used by the API, and therefore in the site editor, `render_block_template_part_php_file`
 * is used for the frontend display.
 *
 * @param WP_Block_Template|null $block_template Return block template object to short-circuit the default query,
 *                                               or null to allow WP to run its normal queries.
 * @param string                 $id             Template unique identifier (example: theme_slug//template_slug).
 * @param string                 $template_type  Template type: `'wp_template'` or '`wp_template_part'`.
 *
 * @return WP_Block_Template|null The PHP template content.
 */
function get_block_template_php_file( $block_template, $id, $template_type ) {
	// Currently, the PHP loader only works for template parts.
	if ( 'wp_template_part' === $template_type ) {
		$parts = explode( '//', $id, 2 );
		if ( count( $parts ) < 2 ) {
			return $block_template;
		}
		// This provides both `theme` and `slug`, but theme should always be the
		// current theme. It's not necessary for the file path as it will fall
		// back correctly.
		list( $theme, $slug ) = $parts;
		$file_path = get_template_part_php_file_path( $slug );
		if ( ! $file_path ) {
			return $block_template;
		}

		$content = get_template_part_php_file_contents( $file_path, true );

		$block_template                 = new WP_Block_Template();
		$block_template->id             = $theme . '//' . $slug;
		$block_template->theme          = $theme;
		$block_template->content        = $content;
		$block_template->slug           = $slug;
		$block_template->source         = 'theme';
		$block_template->type           = $template_type;
		$block_template->title          = $slug;
		$block_template->status         = 'publish';
		$block_template->has_theme_file = true;
		$block_template->is_custom      = true;
		$block_template->area           = WP_TEMPLATE_PART_AREA_UNCATEGORIZED;
	}

	return $block_template;
}

/**
 * Get a full file path from a theme (parent or child).
 *
 * @param string $slug The file slug.
 *
 * @return string|false A full file path, or false if not found.
 */
function get_template_part_php_file_path( $slug ) {
	$parent_theme_folders        = get_block_theme_folders( get_template() );
	$child_theme_folders         = get_block_theme_folders( get_stylesheet() );
	$child_theme_part_file_path  = get_theme_file_path( '/' . $child_theme_folders['wp_template_part'] . '/' . $slug . '.php' );
	$parent_theme_part_file_path = get_theme_file_path( '/' . $parent_theme_folders['wp_template_part'] . '/' . $slug . '.php' );
	$template_part_file_path     = 0 === validate_file( $slug ) && file_exists( $child_theme_part_file_path ) ? $child_theme_part_file_path : $parent_theme_part_file_path;
	if ( 0 === validate_file( $slug ) && file_exists( $template_part_file_path ) ) {
		return $template_part_file_path;
	}
	return false;
}

/**
 * Get the rendered contents of a given PHP file (as plain HTML).
 *
 * @param string $file_path Full path to file.
 * @param string $raw       True for raw block content, false to parse the blocks.
 *
 * @return string The file contents.
 */
function get_template_part_php_file_contents( $file_path, $raw = false ) {
	// Output buffering to capture the resulting HTML.
	ob_start();
	require $file_path;
	$content = ob_get_clean();

	if ( ! $raw ) {
		$content = do_blocks( $content );
		$content = wptexturize( $content );
		$content = convert_smilies( $content );
		$content = shortcode_unautop( $content );
		$content = wp_filter_content_tags( $content );
		$content = do_shortcode( $content );

		// Handle embeds for block template parts.
		global $wp_embed;
		$content = $wp_embed->autoembed( $content );

	}

	return $content;
}
