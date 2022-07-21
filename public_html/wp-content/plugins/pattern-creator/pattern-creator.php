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

require_once __DIR__ . '/includes/mock-blocks.php';

/**
 * Check the conditions of the page to determine if the editor should load.
 * - It should be a single pattern page.
 * - The query variable is present.
 *
 * Permissions are checked in the template itself, so the correct status/login messages can be shown.
 *
 * @return boolean
 */
function should_load_creator() {
	global $wp_query;
	$is_editor = $wp_query->is_singular( POST_TYPE ) && false !== $wp_query->get( IS_EDIT_VAR, false );
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

	if (
		! ( is_editing_pattern() && current_user_can( 'edit_pattern', get_query_var( PATTERN_ID_VAR ) ) ) &&
		! ( ! is_editing_pattern() && is_user_logged_in() )
	) {
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
	wp_set_script_translations( 'wp-pattern-creator', 'wporg-patterns' );

	wp_add_inline_script(
		'wp-pattern-creator',
		sprintf(
			'var wporgLocale = JSON.parse( decodeURIComponent( \'%s\' ) );',
			rawurlencode( wp_json_encode( get_locale() ) )
		),
		'before'
	);

	wp_add_inline_script(
		'wp-pattern-creator',
		sprintf(
			'var wporgBlockPattern = JSON.parse( decodeURIComponent( \'%s\' ) );',
			rawurlencode( wp_json_encode( array(
				'siteUrl' => esc_url( home_url() ),
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
		// Set up the default locale.
		update_post_meta( $post_id, 'wpop_locale', 'en_US' );
	}

	$custom_settings = array(
		'postId'                               => $post_id,
		'siteUrl'                              => site_url(),
		'postsPerPage'                         => get_option( 'posts_per_page' ),
		'styles'                               => gutenberg_get_editor_styles(),
		'__experimentalBlockPatterns'          => array(),
		'__experimentalBlockPatternCategories' => array(),
	);

	wp_deregister_script( 'wporg-global-header-script' );
	$editor_context = new WP_Block_Editor_Context( array( 'post' => $post ) );
	$settings       = get_block_editor_settings( $custom_settings, $editor_context );

	$settings['defaultStatus'] = get_option( 'wporg-pattern-default_status', 'publish' );

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
	// Load layout and margin styles.
	wp_enqueue_style( 'wp-editor-classic-layout-styles' );
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
// Priority needs to be over 1000 to override `logged-out-admin-bar`.
add_filter( 'show_admin_bar', __NAMESPACE__ . '\show_admin_bar', 1001 );

/**
 * Filter out `upload_files` from all non-admin users.
 *
 * @param bool[] $allcaps Array of key/value pairs where keys represent a capability name
 *                        and boolean values represent whether the user has that capability.
 */
function disallow_uploads( $allcaps ) {
	if ( ! isset( $allcaps['manage_options'] ) ) {
		$allcaps['upload_files'] = false;
	}
	return $allcaps;
}
add_filter( 'user_has_cap', __NAMESPACE__ . '\disallow_uploads' );

/**
 * Set up any custom endpoints.
 */
function rest_api_init() {
	require_once __DIR__ . '/includes/openverse-client.php';
	require_once __DIR__ . '/includes/openverse-rest-controller.php';
	$controller = new \Openverse_REST_Controller();
	$controller->register_routes();
}
add_action( 'rest_api_init', __NAMESPACE__ . '\rest_api_init' );

/**
 * Filter editor settings to add extra styles to the Pattern Creator editor.
 *
 * This adds `link` & `style` tags to be loaded into the editor's iframe.
 * - Load Twenty Twenty-One styles for a theme preview
 * - Set the editor background to white for a cleaner preview
 * - Add layout styles for the pattern container so that alignments work
 *
 * @param array $settings Default editor settings.
 * @return array Updated settings.
 */
function add_theme_styles_to_editor( $settings ) {
	if ( ! isset( $settings['__unstableResolvedAssets']['styles'] ) ) {
		return $settings;
	}

	// Build up the alignment styles to match the layout set in theme.json.
	// See https://github.com/WordPress/gutenberg/blob/9d4b83cbbafcd6c6cbd20c86b572f458fc65ff16/lib/block-supports/layout.php#L38
	$block_gap = wp_get_global_styles( array( 'spacing', 'blockGap' ) );
	$layout = wp_get_global_settings( array( 'layout' ) );
	$style = gutenberg_get_layout_style( '.pattern-block-editor__block-list.is-root-container', $layout, true, $block_gap );

	$settings['__unstableResolvedAssets']['styles'] .=
		'\n<link rel="stylesheet" id="theme-styles" href="https://wp-themes.com/wp-content/themes/twentytwentyone/style.css" media="all" />'; //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
	$settings['__unstableResolvedAssets']['styles'] .=
		'\n<style>body.editor-styles-wrapper { background-color: white; }' . $style . '</style>';

	return $settings;
}
add_filter( 'block_editor_settings_all', __NAMESPACE__ . '\add_theme_styles_to_editor', 20 );

/**
 * Load editor styles (this is copied from Gutenberg 13.6).
 *
 * @see https://github.com/WordPress/gutenberg/blob/release/13.6/lib/compat/wordpress-5.9/edit-site-page.php#L43-L77
 *
 * @return array Editor Styles Setting.
 */
function gutenberg_get_editor_styles() {
	global $editor_styles;

	// Ideally the code is extracted into a reusable function.
	$styles = array();

	if ( $editor_styles && current_theme_supports( 'editor-styles' ) ) {
		foreach ( $editor_styles as $style ) {
			if ( preg_match( '~^(https?:)?//~', $style ) ) {
				$response = wp_remote_get( $style );
				if ( ! is_wp_error( $response ) ) {
					$styles[] = array(
						'css' => wp_remote_retrieve_body( $response ),
					);
				}
			} else {
				$file = get_theme_file_path( $style );
				if ( is_file( $file ) ) {
					$styles[] = array(
						'css'     => file_get_contents( $file ),
						'baseURL' => get_theme_file_uri( $style ),
					);
				}
			}
		}
	}

	return $styles;
}
