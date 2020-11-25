<?php
/**
 * Plugin Name: Block Pattern Creator
 * Description: Create block patterns on the frontend of a site.
 * Version: 1.0.0
 * Requires at least: 5.5
 * Author: WordPress Meta Team
 * Text Domain: wporg-pattern-creator
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace WordPressdotorg\Pattern_Creator;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Check the conditions of the page to determine if the editor should load.
 *
 * @return boolean
 */
function should_load_creator() {
	return \is_singular( POST_TYPE );
}

/**
 * Register & load the assets.
 *
 * @throws \Error If the build files don't exist.
 */
function enqueue_assets() {
	if ( ! should_load_creator() ) {
		return;
	}

	$dir = dirname( __FILE__ );

	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new \Error( 'You need to run `yarn start` or `yarn build` for the Pattern Creator.' );
	}

	$script_asset = require( $script_asset_path );
	wp_register_script(
		'wporg-pattern-creator-script',
		plugins_url( 'build/index.js', __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_set_script_translations( 'wporg-pattern-creator-script', 'wporg-pattern-creator' );

	$settings = array(
		'isRTL' => is_rtl(),
	);
	wp_add_inline_script(
		'wporg-pattern-creator-script',
		sprintf(
			'var wporgBlockPattern = JSON.parse( decodeURIComponent( \'%s\' ) );',
			rawurlencode( wp_json_encode( array(
				'settings'   => $settings,
				'postId'     => get_the_ID(),
				'categories' => get_terms(
					'wporg-pattern-category',
					array(
						'hide_empty' => false,
						'fields' => 'id=>name',
					)
				),
			) ) )
		),
		'before'
	);

	wp_enqueue_script( 'wporg-pattern-creator-script' );

	wp_register_style(
		'wporg-pattern-creator-style',
		plugins_url( 'build/style-index.css', __FILE__ ),
		array(
			'wp-components',
			'wp-block-editor',
			'wp-edit-blocks', // Includes block-library dependencies.
			'wp-format-library',
		),
		filemtime( "$dir/build/style-index.css" )
	);

	wp_enqueue_style( 'wporg-pattern-creator-style' );

	// @todo this will need to be adapted to whatever theme we use for wp.org
	remove_action( 'wp_enqueue_scripts', 'twentytwenty_register_styles' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );

/**
 * Bypass WordPress template system to load only our editor app.
 */
function inject_editor_template( $template ) {
	if ( ! should_load_creator() ) {
		return $template;
	}
	return __DIR__ . '/view/editor.php';
}
add_filter( 'template_include', __NAMESPACE__ . '\inject_editor_template' );
