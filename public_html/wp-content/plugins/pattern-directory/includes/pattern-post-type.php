<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Post_Type;

use Error, WP_Block_Type_Registry;
use function WordPressdotorg\Locales\{ get_locales, get_locales_with_english_names, get_locales_with_native_names };
use function WordPressdotorg\Pattern_Directory\Favorite\get_favorite_count;

const POST_TYPE       = 'wporg-pattern';
const UNLISTED_STATUS = 'unlisted';
const SPAM_STATUS     = 'pending-review';

add_action( 'init', __NAMESPACE__ . '\register_post_type_data' );
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_fields' );
add_action( 'init', __NAMESPACE__ . '\register_post_statuses' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );
add_filter( 'allowed_block_types_all', __NAMESPACE__ . '\remove_disallowed_blocks', 10, 2 );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\disable_block_directory', 0 );
add_filter( 'rest_' . POST_TYPE . '_collection_params', __NAMESPACE__ . '\filter_patterns_collection_params' );
add_filter( 'rest_' . POST_TYPE . '_query', __NAMESPACE__ . '\filter_patterns_rest_query', 10, 2 );
add_filter( 'user_has_cap', __NAMESPACE__ . '\set_pattern_caps' );
add_filter( 'posts_orderby', __NAMESPACE__ . '\filter_orderby_locale', 10, 2 );


/**
 * Registers post types and associated taxonomies, meta data, etc.
 */
function register_post_type_data() {
	register_post_type(
		POST_TYPE,
		array(
			'labels'          => array(
				'name'                     => _x( 'Block Pattern', 'post type general name', 'wporg-patterns' ),
				'singular_name'            => _x( 'Block Pattern', 'post type singular name', 'wporg-patterns' ),
				'add_new'                  => _x( 'Add New', 'block pattern', 'wporg-patterns' ),
				'add_new_item'             => __( 'Add New Pattern', 'wporg-patterns' ),
				'edit_item'                => __( 'Edit Pattern', 'wporg-patterns' ),
				'new_item'                 => __( 'New Pattern', 'wporg-patterns' ),
				'view_item'                => __( 'View Pattern', 'wporg-patterns' ),
				'view_items'               => __( 'View Patterns', 'wporg-patterns' ),
				'search_items'             => __( 'Search Patterns', 'wporg-patterns' ),
				'not_found'                => __( 'No patterns found.', 'wporg-patterns' ),
				'not_found_in_trash'       => __( 'No patterns found in Trash.', 'wporg-patterns' ),
				'all_items'                => __( 'All Block Patterns', 'wporg-patterns' ),
				'archives'                 => __( 'Pattern Archives', 'wporg-patterns' ),
				'attributes'               => __( 'Pattern Attributes', 'wporg-patterns' ),
				'insert_into_item'         => __( 'Insert into block pattern', 'wporg-patterns' ),
				'uploaded_to_this_item'    => __( 'Uploaded to this block pattern', 'wporg-patterns' ),
				'filter_items_list'        => __( 'Filter patterns list', 'wporg-patterns' ),
				'items_list_navigation'    => __( 'Block patterns list navigation', 'wporg-patterns' ),
				'items_list'               => __( 'Block patterns list', 'wporg-patterns' ),
				'item_published'           => __( 'Block pattern published.', 'wporg-patterns' ),
				'item_published_privately' => __( 'Block pattern published privately.', 'wporg-patterns' ),
				'item_reverted_to_draft'   => __( 'Block pattern reverted to draft.', 'wporg-patterns' ),
				'item_scheduled'           => __( 'Block pattern scheduled.', 'wporg-patterns' ),
				'item_updated'             => __( 'Block pattern updated.', 'wporg-patterns' ),
			),
			'description'     => 'Stores publicly shared Block Patterns (predefined block layouts, ready to insert and tweak).',
			'public'          => true,
			'show_in_rest'    => true,
			'rewrite'         => array( 'slug' => 'pattern' ),
			'supports'        => array( 'title', 'editor', 'author', 'custom-fields', 'revisions', 'wporg-internal-notes' ),
			'capability_type' => array( 'pattern', 'patterns' ),
			'map_meta_cap'    => true,
		)
	);

	register_taxonomy(
		'wporg-pattern-category',
		POST_TYPE,
		array(
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'rest_base'         => 'pattern-categories',
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug' => 'categories',
			),
			'capabilities' => array(
				'assign_terms' => 'edit_patterns',
				'edit_terms'   => 'edit_patterns',
			),
		)
	);

	register_taxonomy(
		'wporg-pattern-keyword',
		POST_TYPE,
		array(
			'public'            => true,
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'rest_base'         => 'pattern-keywords',
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug' => 'pattern-keywords',
			),
			'capabilities' => array(
				'assign_terms' => 'edit_patterns',
				'edit_terms'   => 'edit_patterns',
			),

			'labels' => array(
				'name'                       => _x( 'Keywords (Internal)', 'taxonomy general name', 'wporg-patterns' ),
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
		'wpop_keywords',
		array(
			'type'              => 'string',
			'description'       => 'A comma-separated list of keywords for this pattern',
			'single'            => true,
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => __NAMESPACE__ . '\can_edit_this_pattern',
			'show_in_rest'      => array(
				'schema' => array(
					'type'      => 'string',
					'maxLength' => 360,
				),
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
			'show_in_rest'      => array(
				'schema' => array(
					'maxLength' => 360,
					'required'  => true,
				),
			),
		)
	);

	register_post_meta(
		POST_TYPE,
		'wpop_viewport_width',
		array(
			'type'              => 'number',
			'description'       => 'The width of the pattern in the block inserter.',
			'single'            => true,
			'default'           => 800,
			'sanitize_callback' => 'absint',
			'auth_callback'     => __NAMESPACE__ . '\can_edit_this_pattern',
			'show_in_rest'      => array(
				'schema' => array(
					'minimum' => 200,
					'maximum' => 2000,
				),
			),
		)
	);

	register_post_meta(
		POST_TYPE,
		'wpop_block_types',
		array(
			'type'              => 'string',
			'description'       => 'A list of block types this pattern supports for transforms.',
			'single'            => false,
			'sanitize_callback' => function( $value, $key, $type ) {
				return preg_replace( '/[^a-z0-9-\/]/', '', $value );
			},
			'auth_callback'     => __NAMESPACE__ . '\can_edit_this_pattern',
			'show_in_rest'      => array(
				'schema' => array(
					'type' => 'string',
				),
			),
		)
	);

	register_post_meta(
		POST_TYPE,
		'wpop_locale',
		array(
			'type'              => 'string',
			'description'       => 'The language used when creating this pattern.',
			'single'            => true,
			'sanitize_callback' => function( $value ) {
				if ( ! in_array( $value, array_keys( get_locales() ), true ) ) {
					return 'en_US';
				}

				return $value;
			},
			'auth_callback'     => __NAMESPACE__ . '\can_edit_this_pattern',
			'show_in_rest'      => array(
				'schema' => array(
					'type'     => 'string',
					'enum'     => array_keys( get_locales() ),
					'required' => true,
					'default'  => 'en_US',
				),
			),
		)
	);

	register_post_meta(
		POST_TYPE,
		'wpop_unlisted_reason',
		array(
			'type'              => 'string',
			'description'       => 'The ID of a flag reason, used to indicate why a pattern was unlisted.',
			'single'            => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => __NAMESPACE__ . '\can_edit_this_pattern',
			'show_in_rest'      => array(
				'schema' => array(
					'type'     => 'string',
				),
			),
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

	/*
	 * Provide the raw content without requiring the `edit` context.
	 *
	 * We need the raw content because it contains the source code for blocks (the comment delimiters). The rendered
	 * content is considered a "classic block", since it lacks these. The `edit` context would return both raw and
	 * rendered, but it requires more permissions and potentially exposes more content than we need.
	 */
	register_rest_field(
		POST_TYPE,
		'pattern_content',
		array(
			'get_callback' => function() {
				return wp_kses_post( get_the_content() );
			},

			'schema' => array(
				'type'  => 'string',
			),
		)
	);

	/*
	 * Get the author's avatar.
	 */
	register_rest_field(
		POST_TYPE,
		'favorite_count',
		array(
			'get_callback' => function() {
				return get_favorite_count( get_the_ID() );
			},

			'schema' => array(
				'type'    => 'integer',
				'default' => 0,
			),
		)
	);

	/*
	 * Get the author's avatar.
	 */
	register_rest_field(
		POST_TYPE,
		'author_meta',
		array(
			'get_callback' => function( $post ) {
				return array(
					'name'   => esc_html( get_the_author_meta( 'display_name' ) ),
					'url'    => esc_url( home_url( '/author/' . get_the_author_meta( 'user_nicename' ) ) ),
					'avatar' => get_avatar_url( $post['author'], array( 'size' => 64 ) ),
				);
			},

			'schema' => array(
				'type'  => 'object',
				'properties' => array(
					'name' => array(
						'type'  => 'string',
					),
					'url' => array(
						'type'  => 'string',
					),
					'avatar' => array(
						'type'  => 'string',
					),
				),
			),
		)
	);

	// Add the parent pattern (English original) to the endpoint.
	// We only need to set the schema. `WP_REST_Posts_Controller` will output the parent ID if the
	// schema contains the parent property. It also checks that the ID referenced is a valid post.
	register_rest_field(
		POST_TYPE,
		'parent',
		array(
			'schema' => array(
				'description' => __( 'The ID for the original English pattern.', 'wporg-patterns' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			),
		)
	);
}

/**
 * Register custom statuses for patterns.
 *
 * @return void
 */
function register_post_statuses() {
	register_post_status(
		UNLISTED_STATUS,
		array(
			'label'                  => _x( 'Unlisted', 'post status', 'wporg-patterns' ),
			'label_count'            => _nx_noop(
				'Unlisted <span class="count">(%s)</span>',
				'Unlisted <span class="count">(%s)</span>',
				'post status',
				'wporg-patterns'
			),
			'public'                 => false,
			'protected'              => true,
			'show_in_admin_all_list' => true,
		)
	);

	register_post_status(
		SPAM_STATUS,
		array(
			'label'                  => _x( 'Possible Spam', 'post status', 'wporg-patterns' ),
			'label_count'            => _nx_noop(
				'Possible Spam <span class="count">(%s)</span>',
				'Possible Spam <span class="count">(%s)</span>',
				'post status',
				'wporg-patterns'
			),
			'public'                 => false,
			'protected'              => true,
			'show_in_admin_all_list' => true,
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
	if ( function_exists( 'get_current_screen' ) && POST_TYPE !== get_current_screen()->id ) {
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

	$locales = ( is_admin() ) ? get_locales_with_english_names() : get_locales_with_native_names();

	wp_add_inline_script(
		'wporg-pattern-post-type',
		'var wporgLocaleData = ' . wp_json_encode( $locales ) . ';',
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
		'core/navigation-area',
		'core/navigation',
		'core/navigation-link',
		'core/navigation-submenu',
		'core/page-list',
		'core/template-part',
		'core/site-logo',
		'core/site-tagline',
		'core/site-title',
	);
	if ( isset( $block_editor_context->post ) && POST_TYPE === $block_editor_context->post->post_type ) {
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

/**
 * Filter the collection parameters:
 * - set a new default for per_page.
 * - add a new parameter, `author_name`, for a user's nicename slug.
 *
 * @param array $query_params JSON Schema-formatted collection parameters.
 * @return array Filtered parameters.
 */
function filter_patterns_collection_params( $query_params ) {
	if ( isset( $query_params['per_page'] ) ) {
		// Number of patterns per page, should be multiple of 2 and 3 (for 2- and 3-column layouts).
		$query_params['per_page']['default'] = 18;
	}

	$query_params['author_name'] = array(
		'description'       => __( 'Limit result set to patterns by a single author.', 'wporg-patterns' ),
		'type'              => 'string',
		'validate_callback' => function( $value ) {
			$user = get_user_by( 'slug', $value );
			return (bool) $user;
		},
	);

	if ( isset( $query_params['orderby'] ) ) {
		$query_params['orderby']['enum'][] = 'favorite_count';
	}

	return $query_params;
}

/**
 * Filter the arguments passed to the pattern query in the API.
 *
 * @param array           $args    Array of arguments to be passed to WP_Query.
 * @param WP_REST_Request $request The REST API request.
 */
function filter_patterns_rest_query( $args, $request ) {
	$locale = $request->get_param( 'locale' );

	// Prioritise results in the requested locale.
	// Does not limit to only the requested locale, so as to provide results when no translations
	// exist for the locale, or we do not recognise the locale.
	if ( $locale && is_string( $locale ) ) {
		$args['meta_query']['orderby_locale'] = array(
			'key'     => 'wpop_locale',
			'compare' => 'IN',
			// Order in value determines result order
			'value'   => array( $locale, 'en_US' ),
		);
	}

	// Use the `author_name` passed in to the API to request patterns by an author slug, not just an ID.
	if ( isset( $request['author_name'] ) ) {
		$user = get_user_by( 'slug', $request['author_name'] );
		if ( $user ) {
			$args['author'] = $user->ID;
		} else {
			$args['post__in'] = array( -1 );
		}
	}

	$orderby = $request->get_param( 'orderby' );
	if ( 'favorite_count' === $orderby ) {
		$args['orderby'] = 'meta_value_num';
		$args['meta_key'] = 'wporg-pattern-favorites';
	}

	return $args;
}

/**
 * Filters the WP_Query orderby to prioritse the locale when required.
 *
 * @param string    $orderby The SQL orderby clause.
 * @param \WP_Query $query   The WP_Query object.
 * @return string The SQL orderby clause altered to prioritise locales if required.
 */
function filter_orderby_locale( $orderby, $query ) {
	global $wpdb;

	// If this query has the orderby_locale meta_query, sort by it.
	if ( ! empty( $query->meta_query->queries['orderby_locale']['value'] ) ) {
		$values      = array_reverse( $query->meta_query->queries['orderby_locale']['value'] );
		$table_alias = $query->meta_query->get_clauses()['orderby_locale']['alias'];

		$field_placeholders = implode( ', ', array_pad( array(), count( $values ), '%s' ) );
		$locale_orderby     = $wpdb->prepare( "FIELD( {$table_alias}.meta_value, {$field_placeholders} ) DESC", $values ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Order by matching the locale first, and then the queries order.
		$orderby = "{$locale_orderby}, {$orderby}";
	}

	return $orderby;
}

/**
 * Get the post object of a block pattern, or false if it's not a pattern or not found.
 *
 * @param int|WP_Post $post
 *
 * @return WP_Post|false
 */
function get_block_pattern( $post ) {
	$pattern = get_post( $post );
	if ( ! $pattern || POST_TYPE !== $pattern->post_type ) {
		return false;
	}
	return $pattern;
}

/**
 * Give all logged in users caps for creating patterns and related taxonomies.
 *
 * This allows any user in the wp.org network to have these capabilities, without having to have an actual
 * role on the pattern directory site. These caps are only given on the front end, though, because in WP Admin
 * these same caps could allow unintended access.
 *
 * @param array $user_caps A list of primitive caps (keys) and whether user has them (boolean values).
 *
 * @return array
 */
function set_pattern_caps( $user_caps ) {
	// Set corresponding caps for all roles.
	$cap_args = array(
		'capability_type' => array( 'pattern', 'patterns' ),
		'capabilities'    => array(),
		'map_meta_cap'    => true,
	);
	$cap_map = (array) get_post_type_capabilities( (object) $cap_args );

	// Users should have the same permissions for patterns as posts, for example,
	// if they have `edit_posts`, they should be granted `edit_patterns`, and so on.
	foreach ( $user_caps as $cap => $bool ) {
		if ( $bool && isset( $cap_map[ $cap ] ) ) {
			$user_caps[ $cap_map[ $cap ] ] = true;
		}
	}

	// Set caps to allow for front end pattern creation.
	if ( is_user_logged_in() && ! is_admin() ) {
		$user_caps['read']                       = true;
		$user_caps['publish_patterns']           = true;
		$user_caps['edit_patterns']              = true;
		$user_caps['edit_published_patterns']    = true;
		$user_caps['delete_patterns']            = true;
		$user_caps['delete_published_patterns']  = true;
		// Note that `edit_others_patterns` & `delete_others_patterns` are separate capabilities.
	}

	return $user_caps;
}
