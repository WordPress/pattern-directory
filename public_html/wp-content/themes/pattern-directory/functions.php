<?php

namespace WordPressdotorg\Pattern_Directory\Theme;

add_action( 'after_setup_theme', __NAMESPACE__ . '\setup' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\wporg_learn_scripts' );


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
 * Enqueue the CSS styles & scripts.
 *
 * The wporg theme does this with a static version, so we have to have it here too with our own cache-busting version.
 * The version is set to the last modified time during development.
 */
function wporg_learn_scripts() {
	wp_enqueue_style(
		'wporg-style',
		get_theme_file_uri( '/css/style.css' ),
		array( 'dashicons', 'open-sans' ),
		filemtime( __DIR__ . '/css/style.css' )
	);

	wp_enqueue_script(
		'wporg-navigation',
		get_template_directory_uri() . '/js/navigation.js',
		array(),
		filemtime( get_template_directory() . '/js/navigation.js' ),
		true
	);
}
