<?php

namespace WordPressdotorg\Pattern_Directory\Theme;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

add_action( 'after_setup_theme', __NAMESPACE__ . '\setup' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function setup() {
	add_theme_support( 'post-thumbnails' );

	// The parent wporg theme is designed for use on wordpress.org/* and assumes locale-domains are available.
	// Remove hreflang support.
	remove_action( 'wp_head', 'WordPressdotorg\Theme\hreflang_link_attributes' );
}

/**
 * Enqueue styles & scripts.
 *
 * The wporg theme registers these with static versions, so we need to override with dynamic versions for
 * cache-busting. The version is set to the last modified time during development.
 */
function enqueue_assets() {
	wp_enqueue_style(
		'wporg-style',
		get_theme_file_uri( '/css/style.css' ),
		array( 'dashicons', 'open-sans' ),
		filemtime( __DIR__ . '/css/style.css' )
	);

	$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
	if ( is_singular( POST_TYPE ) && file_exists( $script_asset_path ) ) {
		$script_asset = require( $script_asset_path );
		wp_enqueue_script(
			'wporg-pattern-script',
			get_theme_file_uri( '/build/index.js' ),
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations( 'wporg-pattern-script', 'wporg-patterns' );
	}
}
