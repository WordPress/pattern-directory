<?php
/**
 * Block Name: Delete Button
 * Description: A button which will delete the current pattern (after an AYS).
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Delete_Button_Block;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register the block.
 */
function init() {
	register_block_type( __DIR__ . '/../../../build/blocks/delete-button' );
}
