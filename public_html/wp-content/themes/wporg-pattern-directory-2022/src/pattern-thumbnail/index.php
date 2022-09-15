<?php
/**
 * Block Name: Pattern Thumbnail
 * Description: Display a random heading from a set.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2022\Pattern_Thumbnail_Block;

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
		dirname( dirname( __DIR__ ) ) . '/build/pattern-thumbnail',
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
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'style' => 'height: 202.667px;pointer-events: none;'
		)
	);
	return sprintf(
		'<div %1$s><iframe title="Pattern Preview" tabindex="-1" style="border: medium none; width: 1200px; max-width: none; height: 800px; transform: scale(0.253333); transform-origin: left top 0px; pointer-events: none;" src="%2$s"></iframe></div>',
		$wrapper_attributes,
		get_permalink( $block->context['postId'] ) . 'view/'
	);
}
