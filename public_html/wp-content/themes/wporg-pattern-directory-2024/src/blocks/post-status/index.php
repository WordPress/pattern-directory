<?php
/**
 * Block Name: Post Status
 * Description: A label displaying the current pattern's status.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Post_Status_Block;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register the block.
 */
function init() {
	register_block_type(
		__DIR__ . '/../../../build/blocks/post-status',
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
	$post_id = $block->context['postId'];
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		return '';
	}

	$status = get_post_status( $post_id );

	$label = '';
	switch ( $status ) {
		case 'publish':
			$label = __( 'Published', 'wporg-patterns' );
			$type = 'published';
			break;
		case 'pending-review': // Potential spam.
		case 'pending':
			$label = __( 'Pending', 'wporg-patterns' );
			$type = 'pending';
			break;
		case 'draft':
			$label = __( 'Draft', 'wporg-patterns' );
			$type = 'draft';
			break;
		case 'unlisted':
			$label = __( 'Declined', 'wporg-patterns' );
			$type = 'unlisted';
			break;
	}

	if ( ! $label ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes( [ 'class' => 'is-' . $type ] );
	return sprintf( '<div %1s>%2s</div>', $wrapper_attributes, $label );
}
