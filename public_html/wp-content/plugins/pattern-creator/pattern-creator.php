<?php
/**
 * Plugin Name: Block Pattern Creator
 * Description: Create block patterns on the frontend of a site.
 * Version: 1.0.0
 * Requires at least: 5.5
 * Author: WordPress Meta Team
 * Text Domain: wporg-patterns
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace WordPressdotorg\Pattern_Creator;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use WP_Block_Editor_Context;

const AUTOSAVE_INTERVAL = 30;
const IS_EDIT_VAR = 'edit-pattern';
const PATTERN_ID_VAR = 'pattern-id';

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
	$is_editor = $wp_query->is_singular( POST_TYPE ) && false !== $wp_query->get( IS_EDIT_VAR, false );
	// @todo Should this be a page template? Something else?
	$is_new = is_page( 'new-pattern' );
	return $is_editor || $is_new;
}

/**
 * Returns whether the pattern is being edited
 *
 * @return boolean
 */
function is_editing_pattern() {
	return '' !== get_query_var( PATTERN_ID_VAR );
}

/**
 * Add our custom parameter to the list of public query variables.
 *
 * @param string[] $query_vars The array of allowed query variable names.
 * @return stringp[] New query vars.
 */
function add_query_var( $query_vars ) {
	$query_vars[] = IS_EDIT_VAR;
	$query_vars[] = PATTERN_ID_VAR;
	return $query_vars;
}
add_filter( 'query_vars', __NAMESPACE__ . '\add_query_var' );

/**
 * Register & load the assets, initialize pattern creator.
 *
 * @throws \Error If the build files don't exist.
 */
function pattern_creator_init() {
	global $editor_styles;

	if ( ! should_load_creator() ) {
		return;
	}

	wp_deregister_style( 'wporg-style' );

	$dir = dirname( __FILE__ );
	$script_asset_path = "$dir/build/index.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new \Error( 'You need to run `yarn start` or `yarn build` for the Pattern Creator.' );
	}

	$script_asset = require( $script_asset_path );
	wp_enqueue_script(
		'wp-pattern-creator',
		plugins_url( 'build/index.js', __FILE__ ),
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);
	wp_set_script_translations( 'wporg-pattern-creator-script', 'wporg-pattern-creator' );

	wp_add_inline_script(
		'wp-pattern-creator',
		sprintf(
			'var wporgBlockPattern = JSON.parse( decodeURIComponent( \'%s\' ) );',
			rawurlencode( wp_json_encode( array(
				'siteUrl'    => esc_url( home_url() ),
			) ) )
		),
		'before'
	);

	wp_enqueue_style(
		'wp-pattern-creator',
		plugins_url( 'build/style-index.css', __FILE__ ),
		array( 'wp-components' ),
		filemtime( "$dir/build/style-index.css" )
	);

	/** Load in admin post functions for `get_default_post_to_edit`. */
	require_once ABSPATH . 'wp-admin/includes/post.php';

	if ( is_singular( POST_TYPE ) || is_editing_pattern() ) {
		$post_id = is_editing_pattern() ? $post_id = get_query_var( PATTERN_ID_VAR ) : get_the_ID();
		$post    = get_post( $post_id );
	} else {
		$post    = get_default_post_to_edit( POST_TYPE, true );
		$post_id = $post->ID;
	}

	$custom_settings = array(
		'postId'                               => $post_id,
		'siteUrl'                              => site_url(),
		'postsPerPage'                         => get_option( 'posts_per_page' ),
		'styles'                               => gutenberg_get_editor_styles(),
		'__experimentalBlockPatterns'          => array(),
		'__experimentalBlockPatternCategories' => array(),
	);
	$editor_context = new WP_Block_Editor_Context( array( 'post' => $post ) );
	$settings       = gutenberg_get_block_editor_settings( $custom_settings, $editor_context );

	gutenberg_initialize_editor(
		'block-pattern-creator',
		'pattern-creator',
		array(
			'preload_paths'    => array(),
			'initializer_name' => 'initialize',
			'editor_settings'  => $settings,
		)
	);

	wp_add_inline_script(
		'wp-blocks',
		sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( get_block_categories( $post ) ) ),
		'after'
	);

	wp_enqueue_script( 'wp-edit-site' );
	wp_enqueue_script( 'wp-format-library' );
	wp_enqueue_style( 'wp-edit-site' );
	wp_enqueue_style( 'wp-format-library' );
	wp_enqueue_media();
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\pattern_creator_init', 20 );

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

/**
 * Add a rewrite rule to handle editing a pattern.
 */
function rewrite_for_pattern_editing() {
	add_rewrite_rule( '^pattern/(\d+)/edit', 'index.php?pagename=new-pattern&' . PATTERN_ID_VAR . '=$matches[1]', 'top' );
}
add_action( 'init', __NAMESPACE__ . '\rewrite_for_pattern_editing' );

/**
 * Inject the editor styles into the page.
 */
function wp_footer_creator() {
	if ( ! should_load_creator() ) {
		return;
	}

	gutenberg_extend_block_editor_styles_html();
}
add_action( 'wp_footer', __NAMESPACE__ . '\wp_footer_creator' );


/**
 * Always disable the admin bar on the creator page.
 *
 * @param bool $show_admin_bar Whether the admin bar should be shown. Default false.
 * @return bool Filtered value.
 */
function show_admin_bar( $show_admin_bar ) {
	if ( ! should_load_creator() ) {
		return $show_admin_bar;
	}

	return false;
}
add_filter( 'show_admin_bar', __NAMESPACE__ . '\show_admin_bar' );
