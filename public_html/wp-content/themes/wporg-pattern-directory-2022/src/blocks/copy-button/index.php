<?php
/**
 * Block Name: Copy Button
 * Description: A button which will copy the current pattern code to the clipboard.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2022\Copy_Button_Block;

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
		__DIR__ . '/../../../build/blocks/copy-button',
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
	$variant = $attributes[ 'variant' ] ?? 'default';

	$post_id = $block->context['postId'];
	if ( ! $post_id ) {
		return '';
	}

	$post = get_post( $post_id );

	// Render a disabled button until the JS kicks in.
	$button = '<button class="wp-block-button__link wp-element-button" disabled="disabled">';
	$button .= __( 'Copy pattern', 'wporg-patterns' );
	$button .= '</button>';
	$button .= '<input class="wp-block-wporg-copy-button__content" type="hidden" value="' . rawurlencode( wp_json_encode( $post->post_content ) ) . '" />';

	$wrapper_attributes = get_block_wrapper_attributes();

	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$button
	);
}
