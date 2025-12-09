<?php
/**
 * Block Name: Pattern View Control, Pattern Preview
 * Description: Two blocks, a pattern preview, with a wrapper block to control the viewport size.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Pattern_Preview_Blocks;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register the blocks.
 */
function init() {
	register_block_type( __DIR__ . '/../../../build/blocks/pattern-preview/controls' );
	register_block_type( __DIR__ . '/../../../build/blocks/pattern-preview/frame' );
}
