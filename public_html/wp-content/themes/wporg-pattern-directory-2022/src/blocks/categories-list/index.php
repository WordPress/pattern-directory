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

	$wrapper_attributes = get_block_wrapper_attributes();
	return sprintf(
		'<ul %1$s>%2$s</ul>',
		$wrapper_attributes,
		$list
	);
}
