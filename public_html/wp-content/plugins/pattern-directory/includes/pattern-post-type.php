<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Post_Type;
use Error;

const POST_TYPE = 'wporg-pattern';

add_action( 'init', __NAMESPACE__ . '\register_post_type_data' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );


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
				'name'                       => _x( 'Keywords', 'taxonomy general name' ),
				'singular_name'              => _x( 'Keyword', 'taxonomy singular name' ),
				'search_items'               => __( 'Search Keywords' ),
				'popular_items'              => __( 'Popular Keywords' ),
				'all_items'                  => __( 'All Keywords' ),
				'edit_item'                  => __( 'Edit Keyword' ),
				'view_item'                  => __( 'View Keyword' ),
				'update_item'                => __( 'Update Keyword' ),
				'add_new_item'               => __( 'Add New Keyword' ),
				'new_item_name'              => __( 'New Keyword Name' ),
				'separate_items_with_commas' => __( 'Separate keywords with commas' ),
				'add_or_remove_items'        => __( 'Add or remove keywords' ),
				'choose_from_most_used'      => __( 'Choose from the most used keywords' ),
				'not_found'                  => __( 'No keywords found.' ),
				'no_terms'                   => __( 'No keywords' ),
				'items_list_navigation'      => __( 'Keywords list navigation' ),
				'items_list'                 => __( 'Keywords list' ),
				/* translators: Tab heading when selecting from the most used terms. */
				'most_used'                  => _x( 'Most Used', 'keywords' ),
				'back_to_items'              => __( '&larr; Go to Keywords' ),
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

	/*
	 * Provide category and keyword slugs via meta.
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
	register_post_meta(
		POST_TYPE,
		'wpop_category_slugs',
		array(
			'type'          => 'array',
			'single'        => true,
			'auth_callback' => '__return_false', // Generated dynamically, should not be stored in database.

			'show_in_rest' => array(
				'schema' => array(
					'items' => array(
						'type' => 'string',
					),
				),

				'prepare_callback' => function() {
					$slugs = wp_list_pluck( wp_get_object_terms( get_the_ID(), 'wporg-pattern-category' ), 'slug' );

					return array_map( 'sanitize_title', $slugs );
				},
			),
		)
	);

	// See `wpop_category_slugs` registration for details.
	register_post_meta(
		POST_TYPE,
		'wpop_keyword_slugs',
		array(
			'type'          => 'array',
			'single'        => true,
			'auth_callback' => '__return_false', // Generated dynamically, should not be stored in database.

			'show_in_rest' => array(
				'schema' => array(
					'items' => array(
						'type' => 'string',
					),
				),

				'prepare_callback' => function() {
					$slugs = wp_list_pluck( wp_get_object_terms( get_the_ID(), 'wporg-pattern-keyword' ), 'slug' );

					return array_map( 'sanitize_title', $slugs );
				},
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
