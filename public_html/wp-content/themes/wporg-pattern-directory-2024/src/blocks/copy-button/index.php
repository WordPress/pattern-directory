<?php
/**
 * Block Name: Copy Button
 * Description: A button which will copy the current pattern code to the clipboard.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Copy_Button_Block;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register the block.
 */
function init() {
	register_block_type( __DIR__ . '/../../../build/blocks/copy-button' );
}
