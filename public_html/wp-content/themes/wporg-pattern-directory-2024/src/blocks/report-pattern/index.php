<?php
/**
 * Block Name: Report Pattern
 * Description: A button to trigger the report flow, including report modal.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Report_Pattern_Block;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register the block.
 */
function init() {
	register_block_type( __DIR__ . '/../../../build/blocks/report-pattern' );
}
