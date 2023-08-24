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
use function WordPressdotorg\MU_Plugins\Global_Header_Footer\{ is_rosetta_site, get_rosetta_name };
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
			"var wporgLocale = JSON.parse( decodeURIComponent( '%s' ) );",
			rawurlencode( wp_json_encode( array(
				'id' => get_locale(),
				'displayName' => is_rosetta_site() ? get_rosetta_name() : '',
			) ) ),
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

	add_filter( 'template', __NAMESPACE__ . '\set_theme_twentytwentythree' );
	add_filter( 'stylesheet', __NAMESPACE__ . '\set_theme_twentytwentythree' );

	$custom_settings = array(
		'postId'                               => $post_id,
		'siteUrl'                              => site_url(),
		'postsPerPage'                         => get_option( 'posts_per_page' ),
		'styles'                               => get_block_editor_theme_styles(),
		'__experimentalBlockPatterns'          => array(),
		'__experimentalBlockPatternCategories' => array(),
	);

	wp_deregister_script( 'wporg-global-header-script' );

	$editor_context = new WP_Block_Editor_Context( array( 'post' => $post ) );
	$settings       = get_block_editor_settings( $custom_settings, $editor_context );

	$settings['defaultStatus'] = get_option( 'wporg-pattern-default_status', 'publish' );

	remove_filter( 'template', __NAMESPACE__ . '\set_theme_twentytwentythree' );
	remove_filter( 'stylesheet', __NAMESPACE__ . '\set_theme_twentytwentythree' );

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

	// Allow the post type labels through the `types` endpoint when viewing.
	// This passes the value back to unauthenticated users, which prevents JS
	// errors when the post-date block tries to use them.
	register_rest_field(
		'type', // The object-type for the `types` endpoint.
		'labels',
		array(
			'schema' => array(
				'description' => __( 'Human-readable labels for the post type for various contexts.', 'wporg-patterns' ),
				'type'        => 'object',
				'context'     => array( 'edit', 'view' ),
				'readonly'    => true,
			),
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\rest_api_init' );

/**
 * Return the appropriate theme slug for the current environment.
 *
 * @return string Theme slug.
 */
function set_theme_twentytwentythree() {
	if ( 'local' === wp_get_environment_type() ) {
		return 'twentytwentythree';
	} else {
		return 'core/twentytwentythree';
	}
}

/**
 * Temporarily restore gutenberg_initialize_editor() for compat with Gutenberg 16.5.0
 *
 * @see https://github.com/WordPress/pattern-directory/issues/601
 */
function gutenberg_initialize_editor( $editor_name, $editor_script_handle, $settings ) {

	$defaults = array(
		'preload_paths'    => array(),
		'initializer_name' => 'initialize',
		'editor_settings'  => array(),
	);

	$settings = wp_parse_args( $settings, $defaults );

	/**
	 * Preload common data by specifying an array of REST API paths that will be preloaded.
	 *
	 * Filters the array of paths that will be preloaded.
	 *
	 * @param string[] $preload_paths Array of paths to preload.
	 */
	$preload_paths = apply_filters( "{$editor_name}_preload_paths", $settings['preload_paths'] );

	$preload_data = array_reduce(
		$preload_paths,
		'rest_preload_api_request',
		array()
	);

	wp_add_inline_script(
		'wp-api-fetch',
		sprintf(
			'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
			wp_json_encode( $preload_data )
		),
		'after'
	);
	wp_add_inline_script(
		"wp-{$editor_script_handle}",
		sprintf(
			'wp.domReady( function() {
				wp.%s.%s( "%s", %s );
			} );',
			lcfirst( str_replace( '-', '', ucwords( $editor_script_handle, '-' ) ) ),
			$settings['initializer_name'],
			str_replace( '_', '-', $editor_name ),
			wp_json_encode( $settings['editor_settings'] )
		)
	);

	// Preload server-registered block schemas.
	wp_add_inline_script(
		'wp-blocks',
		'wp.blocks.unstable__bootstrapServerSideBlockDefinitions(' . wp_json_encode( get_block_editor_server_block_settings() ) . ');'
	);
}
