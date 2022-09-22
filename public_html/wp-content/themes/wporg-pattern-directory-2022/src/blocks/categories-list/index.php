<?php
/**
 * Block Name: Pattern Categories List
 * Description: Display the pattern categories.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2022\Categories_List_Block;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type(
		__DIR__ . '/../../../build/blocks/categories-list',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}

/**
 * Render the block content.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	$args = array(
		'taxonomy'     => 'wporg-pattern-category',
		'echo'         => false,
		'hierarchical' => false,
		'orderby'      => 'name',
		'title_li'     => '',
		'show_option_all' => __( 'All', 'wporg' ),
	);
	$list = wp_list_categories( $args );

	$block_args = array();
	if ( ! is_tax( 'wporg-pattern-category' ) && ! isset( $_GET['wporg-pattern-category'] ) ) {
		$block_args['class'] = 'has-all-cats';
	}

	$wrapper_attributes = get_block_wrapper_attributes( $block_args );
	return sprintf(
		'<ul %1$s>%2$s</ul>',
		$wrapper_attributes,
		$list
	);
}

/**
 * Adjust the URL for category filtering on other pages, ex author archives, favorites.
 *
 * @param array   $atts              The HTML attributes applied to the list item's
 *                                   `<a>` element, empty strings are ignored.
 * @param WP_Term $category          Term data object.
 *
 * @return array Filtered attributes.
 */
add_filter(
	'category_list_link_attributes',
	function( $atts, $category ) {
		global $wp;
		if ( ! is_home() ) {
			$path = $wp->request ?? '/';
			$path = trailingslashit( preg_replace( '/\/page\/[\d]+/', '', $path ) );
			$atts['href'] = add_query_arg( 'wporg-pattern-category', $category->slug, home_url( $path ) );
		}
		return $atts;
	},
	10,
	2
);

/**
 * Add the "current" class to categories when used in query-loop blocks.
 *
 * @param string[] $css_classes An array of CSS classes to be applied to each list item.
 * @param WP_Term  $category    Category data object.
 *
 * @return string[] Filtered classes.
 */
add_filter(
	'category_css_class',
	function( $css_classes, $category ) {
		if ( isset( $_GET['wporg-pattern-category'] ) ) {
			if ( $category->slug === $_GET['wporg-pattern-category'] ) {
				$css_classes[] = 'current-cat';
			}
		}
		return $css_classes;
	},
	10,
	2
);