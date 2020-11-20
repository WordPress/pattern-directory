<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Post_Type;

const POST_TYPE = 'wporg-pattern';

add_action( 'init', __NAMESPACE__ . '\register_post_type_data' );
add_filter( 'rest_' . POST_TYPE . '_item_schema', __NAMESPACE__ . '\embed_extra_fields_in_search_endpoint' );
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
 * Add extra fields to the post type when it's embedded in the search results endpoint.
 *
 * @param array $schema
 *
 * @return array
 */
function embed_extra_fields_in_search_endpoint( $schema ) {
	$schema['properties']['content']['context'][]                           = 'embed';
	$schema['properties']['content']['properties']['rendered']['context'][] = 'embed';
	$schema['properties']['meta']['context'][]                              = 'embed';
	$schema['properties']['pattern-categories']['context'][]                = 'embed';

	return $schema;
}

/**
 * Enqueue scripts for the block editor.
 *
 * @throws \Error If the build files don't exist.
 */
function enqueue_editor_assets() {
	if ( function_exists( 'get_current_screen' ) && POST_TYPE !== get_current_screen()->id ) {
		return;
	}

	$dir = dirname( dirname( __FILE__ ) );

	$script_asset_path = "$dir/build/pattern-post-type.asset.php";
	if ( ! file_exists( $script_asset_path ) ) {
		throw new \Error( 'You need to run `yarn start` or `yarn build` for the Pattern Directory.' );
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
