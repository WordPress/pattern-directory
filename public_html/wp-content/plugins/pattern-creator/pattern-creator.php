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

const QUERY_VAR = 'edit-pattern';

/**
 * Check the conditions of the page to determine if the editor should load.
 * - It should be a single pattern page.
 * - The current user can edit it.
 * - The query variable is present.
 *
 * @return boolean
 */
function should_load_creator() {
	global $wp_query;
	$is_editor = $wp_query->is_singular( POST_TYPE ) && false !== $wp_query->get( QUERY_VAR, false );
	// @todo Should this be a page template? Something else?
	$is_new = is_page( 'new-pattern' );
	return $is_editor || $is_new;
}

/**
 * Add our custom parameter to the list of public query variables.
 *
 * @param string[] $query_vars The array of allowed query variable names.
 * @return stringp[] New query vars.
 */
function add_query_var( $query_vars ) {
	$query_vars[] = QUERY_VAR;
	return $query_vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\add_query_var' );

/**
 * Register & load the assets.
 *
 * @throws \Error If the build files don't exist.
 */
function enqueue_assets() {
	if ( ! should_load_creator() ) {
		return;
	}

	/** Load in admin post functions for `get_default_post_to_edit`. */
	require_once ABSPATH . 'wp-admin/includes/post.php';

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

	if ( is_singular( POST_TYPE ) ) {
		$post_id = get_the_ID();
	} else {
		$post    = get_default_post_to_edit( POST_TYPE, true );
		$post_id = $post->ID;
	}

	wp_add_inline_script(
		'wporg-pattern-creator-script',
		sprintf(
			'var wporgBlockPattern = JSON.parse( decodeURIComponent( \'%s\' ) );',
			rawurlencode( wp_json_encode( array(
				'settings'   => $settings,
				'postId'     => $post_id,
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

	wp_enqueue_style( 'wp-edit-post' );
	wp_enqueue_style( 'wporg-pattern-creator-style' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets' );

/**
 * Bypass WordPress template system to load only our editor app.
 */
function inject_editor_template( $template ) {
	if ( should_load_creator() ) {
		return __DIR__ . '/view/editor.php';
	}
	return $template;
}
add_filter( 'template_include', __NAMESPACE__ . '\inject_editor_template' );
