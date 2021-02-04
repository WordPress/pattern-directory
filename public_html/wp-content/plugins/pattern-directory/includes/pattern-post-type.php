<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Post_Type;
use Error, WP_Block_Type_Registry;

const POST_TYPE = 'wporg-pattern';

add_action( 'init', __NAMESPACE__ . '\register_post_type_data' );
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_fields' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );
add_filter( 'allowed_block_types', __NAMESPACE__ . '\remove_disallowed_blocks', 10, 2 );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\disable_block_directory', 0 );


/**
 * Registers post types and associated taxonomies, meta data, etc.
 */
function register_post_type_data() {
	register_post_type(
		POST_TYPE,
		array(
			'label'        => 'Block Pattern',
			'description'  => 'Stores publicly shared Block Patterns (predefined block layouts, ready to insert and tweak).',
			'public'       => true,
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => 'pattern' ),
			'supports'     => array( 'title', 'editor', 'author', 'custom-fields' ),
		)
	);

	register_taxonomy(
		'wporg-pattern-category',
		POST_TYPE,
		array(
			'public'        => true,
			'hierarchical'  => true,
			'show_in_rest'  => true,
			'rest_base'     => 'pattern-categories',
		)
	);

	register_taxonomy(
		'wporg-pattern-keyword',
		POST_TYPE,
		array(
			'public'        => true,
			'hierarchical'  => false,
			'show_in_rest'  => true,
			'rest_base'     => 'pattern-keywords',

			'labels' => array(
				'name'                       => _x( 'Keywords', 'taxonomy general name', 'wporg-patterns' ),
				'singular_name'              => _x( 'Keyword', 'taxonomy singular name', 'wporg-patterns' ),
				'search_items'               => __( 'Search Keywords', 'wporg-patterns' ),
				'popular_items'              => __( 'Popular Keywords', 'wporg-patterns' ),
				'all_items'                  => __( 'All Keywords', 'wporg-patterns' ),
				'edit_item'                  => __( 'Edit Keyword', 'wporg-patterns' ),
				'view_item'                  => __( 'View Keyword', 'wporg-patterns' ),
				'update_item'                => __( 'Update Keyword', 'wporg-patterns' ),
				'add_new_item'               => __( 'Add New Keyword', 'wporg-patterns' ),
				'new_item_name'              => __( 'New Keyword Name', 'wporg-patterns' ),
				'separate_items_with_commas' => __( 'Separate keywords with commas', 'wporg-patterns' ),
				'add_or_remove_items'        => __( 'Add or remove keywords', 'wporg-patterns' ),
				'choose_from_most_used'      => __( 'Choose from the most used keywords', 'wporg-patterns' ),
				'not_found'                  => __( 'No keywords found.', 'wporg-patterns' ),
				'no_terms'                   => __( 'No keywords', 'wporg-patterns' ),
				'items_list_navigation'      => __( 'Keywords list navigation', 'wporg-patterns' ),
				'items_list'                 => __( 'Keywords list', 'wporg-patterns' ),
				/* translators: Tab heading when selecting from the most used terms. */
				'most_used'                  => _x( 'Most Used', 'keywords', 'wporg-patterns' ),
				'back_to_items'              => __( '&larr; Go to Keywords', 'wporg-patterns' ),
			),
		)
	);

	register_post_meta(
		POST_TYPE,
		'wpop_description',
		array(
			'type'              => 'string',
			'description'       => 'A description of the pattern',
			'single'            => true,
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => __NAMESPACE__ . '\can_edit_this_pattern',
			'show_in_rest'      => true,
		)
	);

	register_post_meta(
		POST_TYPE,
		'wpop_viewport_width',
		array(
			'type'              => 'number',
			'description'       => 'The width of the pattern in the block inserter.',
			'single'            => true,
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'auth_callback'     => __NAMESPACE__ . '\can_edit_this_pattern',
			'show_in_rest'      => true,
		)
	);
}

/**
 * Adds extra fields to REST API responses.
 */
function register_rest_fields() {
	/*
	 * Provide category and keyword slugs without embedding.
	 *
	 * Normally API clients would request these via `_embed` parameters, but that would returning the entire
	 * object, and Core only needs the slugs. We'd also have to include the `_links` field, because of a Core bug.
	 *
	 * @see https://core.trac.wordpress.org/ticket/49538
	 * @see https://core.trac.wordpress.org/ticket/49985
	 *
	 * Adding it here is faster for the server to generate, and for the client to download. It also makes the
	 * output easier for a human to visually parse.
	 */
	register_rest_field(
		POST_TYPE,
		'category_slugs',
		array(
			'get_callback' => function() {
				$slugs = wp_list_pluck( wp_get_object_terms( get_the_ID(), 'wporg-pattern-category' ), 'slug' );

				return array_map( 'sanitize_title', $slugs );
			},

			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type' => 'string',
				),
			),
		)
	);

	// See `category_slugs` registration for details.
	register_rest_field(
		POST_TYPE,
		'keyword_slugs',
		array(
			'get_callback' => function() {
				$slugs = wp_list_pluck( wp_get_object_terms( get_the_ID(), 'wporg-pattern-keyword' ), 'slug' );

				return array_map( 'sanitize_title', $slugs );
			},

			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type' => 'string',
				),
			),
		)
	);
}

/**
 * Determines if the current user can edit the given pattern post.
 *
 * This is a callback for the `auth_{$object_type}_meta_{$meta_key}` filter, and it's used to authorize access to
 * modifying post meta keys via the REST API.
 *
 * @param bool   $allowed
 * @param string $meta_key
 * @param int    $pattern_id
 *
 * @return bool
 */
function can_edit_this_pattern( $allowed, $meta_key, $pattern_id ) {
	return current_user_can( 'edit_post', $pattern_id );
}

/**
 * Enqueue scripts for the block editor.
 *
 * @throws Error If the build files don't exist.
 */
function enqueue_editor_assets() {
	if ( POST_TYPE !== get_current_screen()->id ) {
		return;
	}

	$dir = dirname( dirname( __FILE__ ) );

	$script_asset_path = "$dir/build/pattern-post-type.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error( 'You need to run `yarn start` or `yarn build` for the Pattern Directory.' );
	}

	$script_asset = require( $script_asset_path );
	wp_enqueue_script(
		'wporg-pattern-post-type',
		plugins_url( 'build/pattern-post-type.js', dirname( __FILE__ ) ),
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_set_script_translations( 'wporg-pattern-post-type', 'wporg-patterns' );
}

/**
 * Restrict the set of blocks allowed in block patterns.
 *
 * @param bool|array $allowed_block_types Array of block type slugs, or boolean to enable/disable all.
 * @param WP_Post    $post                The post resource data.
 *
 * @return bool|array A (possibly) filtered list of block types.
 */
function remove_disallowed_blocks( $allowed_block_types, $post ) {
	$disallowed_block_types = array(
		// Remove blocks that don't make sense in Block Patterns
		'core/freeform', // Classic block
		'core/legacy-widget',
		'core/more',
		'core/nextpage',
		'core/block', // Reusable blocks
		'core/shortcode',
	);
	if ( POST_TYPE === $post->post_type ) {
		// This can be true if all block types are allowed, so to filter them we
		// need to get the list of all registered blocks first.
		if ( true === $allowed_block_types ) {
			$allowed_block_types = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
		}
		return array_values( array_diff( $allowed_block_types, $disallowed_block_types ) );
	}
	return $allowed_block_types;
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
