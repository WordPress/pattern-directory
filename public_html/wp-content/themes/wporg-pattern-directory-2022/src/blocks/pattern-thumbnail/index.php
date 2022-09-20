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
		__DIR__ . '/../../../build/blocks/pattern-thumbnail',
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
	if ( ! empty( $block->block_type->view_script ) ) {
		wp_enqueue_script( $block->block_type->view_script );
		// Move to footer.
		wp_script_add_data( $block->block_type->view_script, 'group', 1 );
	}
	$url = add_query_arg( 'view', true, get_permalink( $block->context['postId'] ) );

	$wrapper_attributes = get_block_wrapper_attributes();
	return sprintf(
		'<div %1$s data-url="%2$s"></div>',
		$wrapper_attributes,
		esc_attr( $url )
	);
}
