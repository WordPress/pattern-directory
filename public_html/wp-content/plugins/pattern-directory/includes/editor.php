<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Post_Type\Editor;

use Error, WP_Block_Type_Registry;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use function WordPressdotorg\Locales\{ get_locales_with_english_names, get_locales_with_native_names };
use function WordPressdotorg\MU_Plugins\Global_Header_Footer\{ is_rosetta_site, get_rosetta_name };

const SCRIPT_HANDLE = 'wporg-pattern-editor';

// Actions & filters
add_action( 'setup_theme', __NAMESPACE__ . '\set_default_theme', 5 );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );
add_filter( 'allowed_block_types_all', __NAMESPACE__ . '\remove_disallowed_blocks', 10, 2 );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\disable_block_directory', 0 );

// Remove patterns — happens site-wide, but that's okay on this site.
remove_action( 'init', '_register_core_block_patterns_and_categories' );
add_filter( 'should_load_remote_block_patterns', '__return_false' );

add_action(
	// This action is run before `init`, so we can check and remove the theme pattern registration
	// on just the admin/API responses. This keeps the theme patterns on the frontend, otherwise
	// the site is broken.
	'after_setup_theme',
	function() {
		if ( is_admin() || wp_is_json_request() ) {
			remove_action( 'init', '_register_theme_block_patterns' );
		}
	}
);

/**
 * Check the conditions of the page to determine if we're on a pattern editor.
 *
 * @return boolean
 */
function is_pattern_editor() {
	if ( ! ( defined( 'WP_ADMIN' ) && WP_ADMIN ) ) {
		return false;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

	if ( str_contains( $request_uri, '/post-new.php?post_type=' . POST_TYPE ) ) {
		return true;
	}

	if ( str_contains( $request_uri, '/post.php' ) && isset( $_GET['post'] ) ) {
		return get_post( $_GET['post'] )->post_type === POST_TYPE;
	}

	return false;
}

/**
 * Set up page for customized editor.
 */
function set_default_theme() {
	if ( ! is_pattern_editor() ) {
		return;
	}

	$theme = ( 'local' === wp_get_environment_type() ) ? 'twentytwentyfour' : 'core/twentytwentyfour';

	add_filter(
		'template',
		function() use ( $theme ) {
			return $theme;
		}
	);

	add_filter(
		'stylesheet',
		function() use ( $theme ) {
			return $theme;
		}
	);
}

/**
 * Enqueue scripts for the block editor.
 *
 * @throws Error If the build files don't exist.
 */
function enqueue_editor_assets() {
	if ( function_exists( 'get_current_screen' ) && POST_TYPE !== get_current_screen()->id ) {
		return;
	}

	$dir = dirname( dirname( __FILE__ ) );

	$script_asset_path = "$dir/build/pattern-post-type.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error( 'You need to run `yarn start` or `yarn build` for the Pattern Directory.' );
	}

	$script_asset = require $script_asset_path;
	wp_enqueue_script(
		SCRIPT_HANDLE,
		plugins_url( 'build/pattern-post-type.js', dirname( __FILE__ ) ),
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_set_script_translations( SCRIPT_HANDLE, 'wporg-patterns' );

	$locales = ( is_admin() ) ? get_locales_with_english_names() : get_locales_with_native_names();

	wp_add_inline_script(
		SCRIPT_HANDLE,
		'var wporgLocaleData = ' . wp_json_encode( $locales ) . ';',
		'before'
	);

	wp_add_inline_script(
		SCRIPT_HANDLE,
		sprintf(
			"var wporgLocale = JSON.parse( decodeURIComponent( '%s' ) );",
			rawurlencode( wp_json_encode( array(
				'id' => get_locale(),
				'displayName' => is_rosetta_site() ? get_rosetta_name() : '',
			) ) ),
		),
		'before'
	);

	wp_add_inline_script(
		SCRIPT_HANDLE,
		sprintf(
			'var wporgBlockPattern = JSON.parse( decodeURIComponent( \'%s\' ) );',
			rawurlencode( wp_json_encode( array(
				'siteUrl' => esc_url( home_url() ),
			) ) )
		),
		'before'
	);

	wp_enqueue_style(
		'wporg-pattern-post-type',
		plugins_url( 'build/pattern-post-type.css', dirname( __FILE__ ) ),
		array(),
		$script_asset['version'],
	);
}

/**
 * Restrict the set of blocks allowed in block patterns.
 *
 * @param bool|array              $allowed_block_types  Array of block type slugs, or boolean to enable/disable all.
 * @param WP_Block_Editor_Context $block_editor_context The post resource data.
 *
 * @return bool|array A (possibly) filtered list of block types.
 */
function remove_disallowed_blocks( $allowed_block_types, $block_editor_context ) {
	$disallowed_block_types = array(
		// Remove blocks that don't make sense in Block Patterns
		'core/freeform', // Classic block
		'core/legacy-widget',
		'core/more',
		'core/nextpage',
		'core/block', // Reusable blocks
		'core/shortcode',
		'core/template-part',
	);

	if ( isset( $block_editor_context->post ) && POST_TYPE === $block_editor_context->post->post_type ) {
		// This can be true if all block types are allowed, so to filter them we
		// need to get the list of all registered blocks first.
		if ( true === $allowed_block_types ) {
			$allowed_block_types = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
		}
		$allowed_block_types = array_diff( $allowed_block_types, $disallowed_block_types );

		// Remove the "WordPress.org" blocks, like Global Header & Global Footer.
		$allowed_block_types = array_filter(
			$allowed_block_types,
			function ( $block_type ) {
				return 'wporg/' !== substr( $block_type, 0, 6 );
			}
		);
	}

	return is_array( $allowed_block_types ) ? array_values( $allowed_block_types ) : $allowed_block_types;
}

/**
 * Disable the block directory in wp-admin for patterns.
 *
 * The block directory file isn't loaded on the frontend, so this is only needed for site admins who can open
 * the pattern in the "real" wp-admin editor.
 */
function disable_block_directory() {
	if ( is_admin() && POST_TYPE === get_post_type() ) {
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
		remove_action( 'enqueue_block_editor_assets', 'gutenberg_enqueue_block_editor_assets_block_directory' );
	}
}
