<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Validation;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_content', 10, 2 );

/**
 * Validate the pattern content.
 */
function validate_content( $prepared_post, $request ) {
	$content = $prepared_post->post_content;
	if ( ! $content ) {
		return new \WP_Error(
			'rest_pattern_empty',
			__( 'Pattern content cannot be empty.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	$blocks = parse_blocks( $content );
	$registry = \WP_Block_Type_Registry::get_instance();

	// $blocks contains a list of the blocks in the content. By default it will always have one item, even if it's
	// not valid block content. Instead, we should check that each block in the list has a blockName.
	$invalid_blocks = array_filter( $blocks, function( $block ) use ( $registry ) {
		$block_type = $registry->get_registered( $block['blockName'] );
		return is_null( $block['blockName'] ) || is_null( $block_type );
	} );
	if ( count( $invalid_blocks ) ) {
		return new \WP_Error(
			'rest_pattern_invalid_blocks',
			__( 'Pattern content contains invalid blocks.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	// Next, we should check that we have at least one non-empty block.
	$real_blocks = array_filter( $blocks, function( $block ) use ( $registry ) {
		$block_type = $registry->get_registered( $block['blockName'] );

		// Check if the attributes are different from the default attributes.
		$block_attrs = $block_type->prepare_attributes_for_render( $block['attrs'] );
		$default_attrs = $block_type->prepare_attributes_for_render( array() );
		if ( $block_attrs != $default_attrs ) {
			return true;
		}

		// Try to judge whether a block has text content, or a valid image.
		// First, remove class attributes, since custom class names would be caught above.
		// Next, remove empty alt tags, which are present on default image blocks.
		// Lastly, remove HTML tags without attributes- this regex catches opening, closing, and self-closing tags.
		// After all this, any block_content left should be there intentionally by the author.
		$to_replace = array( '/class="[^"]*"/', '/alt=""/', '/<\/?[a-zA-Z]+\s*\/?>/' );
		$block_content = trim( preg_replace( $to_replace, '', $block['innerHTML'] ) );
		if ( ! empty( $block_content ) ) {
			return true;
		}
		return false;
	} );

	if ( ! count( $real_blocks ) ) {
		return new \WP_Error(
			'rest_pattern_empty_blocks',
			__( 'Pattern content contains only empty blocks.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	return $prepared_post;
}
