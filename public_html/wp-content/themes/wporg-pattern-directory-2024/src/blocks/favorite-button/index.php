<?php
/**
 * Block Name: Favorite Button
 * Description: A button showing the current count of favorites, which can toggle favoriting on the current pattern.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Favorite_Button_Block;

add_action( 'init', __NAMESPACE__ . '\init' );

function init() {
	register_block_type( __DIR__ . '/../../../build/blocks/favorite-button' );
}
