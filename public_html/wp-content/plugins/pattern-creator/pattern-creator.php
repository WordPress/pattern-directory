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
 * Register & load the assets.
 *
 * @throws \Error If the build files don't exist.
 */
function enqueue_assets() {
	if ( ! should_load_creator() ) {
		return;
	}

	wp_deregister_style( 'wporg-style' );

	do_action( 'enqueue_block_editor_assets' );

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

	if ( is_singular( POST_TYPE ) ) {
		$post_id = get_the_ID();
		$post    = get_post( $post_id );
	} else {
		$post    = get_default_post_to_edit( POST_TYPE, true );
		$post_id = $post->ID;
	}

	// Update the post id if we're editing a pattern.
	if ( should_load_creator() && is_editing_pattern() ) {
		$post_id = get_query_var( PATTERN_ID_VAR );
		$post    = get_post( $post_id );
	}

	$settings = array(
		'alignWide'                            => true, // Support wide patterns.
		'allowedBlockTypes'                    => apply_filters( 'allowed_block_types', true, $post ),
		'disablePostFormats'                   => true,
		'enableCustomFields'                   => false,
		'titlePlaceholder'                     => __( 'Add pattern title', 'wporg-patterns' ),
		'bodyPlaceholder'                      => __( 'Start writing or type / to choose a block', 'wporg-patterns' ),
		'isRTL'                                => is_rtl(),
		'autosaveInterval'                     => AUTOSAVE_INTERVAL,
		'maxUploadFileSize'                    => 0,
		'richEditingEnabled'                   => user_can_richedit(),
		'__experimentalBlockPatterns'          => array(),
		'__experimentalBlockPatternCategories' => array(),

		// Editor features -  @todo Re-enable later?
		'disableCustomColors'                  => true,
		'disableCustomFontSizes'               => true,
		'disableCustomGradients'               => true,
		'enableCustomLineHeight'               => false,
		'enableCustomUnits'                    => false,
	);

	wp_add_inline_script(
		'wporg-pattern-creator-script',
		sprintf(
			'var wporgBlockPattern = JSON.parse( decodeURIComponent( \'%s\' ) );',
			rawurlencode( wp_json_encode( array(
				'siteUrl'    => esc_url( home_url() ),
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
			'wp-edit-post',
			'wp-format-library',
		),
		filemtime( "$dir/build/style-index.css" )
	);

	// Postbox is only registered if `is_admin`, so we need to intentionally add it.
	wp_enqueue_script(
		'postbox',
		admin_url( 'js/postbox.min.js' ),
		array( 'jquery-ui-sortable', 'wp-a11y' ),
		get_bloginfo( 'version' ),
		true
	);
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'common' );
	wp_enqueue_style( 'forms' );
	wp_enqueue_style( 'dashboard' );
	wp_enqueue_style( 'media' );
	wp_enqueue_style( 'admin-menu' );
	wp_enqueue_style( 'admin-bar' );
	wp_enqueue_style( 'nav-menus' );
	wp_enqueue_style( 'l10n' );
	wp_enqueue_style( 'buttons' );
	wp_enqueue_style( 'wp-edit-post' );
	wp_enqueue_style( 'wporg-pattern-creator-style' );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_assets', 20 );

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

	if (
		'edit' === filter_input( INPUT_GET, 'action' )
		&& POST_TYPE === get_post_type( filter_input( INPUT_GET, 'post' ) )
		&& ! is_admin()
	) {
		wp_safe_redirect( home_url( '/pattern/' . absint( $_GET['post'] ) . '/edit' ) );
		exit;
	}
}
add_action( 'init', __NAMESPACE__ . '\rewrite_for_pattern_editing' );

