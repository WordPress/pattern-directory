<?php
/**
 * Block Name: Pattern Thumbnail
 * Description: Display a thumbnail for the current pattern.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Pattern_Thumbnail_Block;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type( __DIR__ . '/../../../build/blocks/pattern-thumbnail' );
}
