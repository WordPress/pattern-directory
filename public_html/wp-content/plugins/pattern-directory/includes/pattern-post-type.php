<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Post_Type;

use Error, WP_Block_Type_Registry;
use function WordPressdotorg\Locales\{ get_locales, get_locales_with_english_names, get_locales_with_native_names };
use function WordPressdotorg\Pattern_Directory\Favorite\get_favorite_count;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_REASON;

const POST_TYPE       = 'wporg-pattern';
const UNLISTED_STATUS = 'unlisted';
const SPAM_STATUS     = 'pending-review';

add_action( 'init', __NAMESPACE__ . '\register_post_type_data' );
add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_fields' );
add_action( 'init', __NAMESPACE__ . '\register_post_statuses' );
add_action( 'transition_post_status', __NAMESPACE__ . '\status_transitions', 10, 3 );
add_action( 'post_updated', __NAMESPACE__ . '\update_contains_block_types_meta' );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );
add_filter( 'allowed_block_types_all', __NAMESPACE__ . '\remove_disallowed_blocks', 10, 2 );
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\disable_block_directory', 0 );
add_filter( 'rest_' . POST_TYPE . '_collection_params', __NAMESPACE__ . '\filter_patterns_collection_params' );
add_filter( 'rest_' . POST_TYPE . '_query', __NAMESPACE__ . '\filter_patterns_rest_query', 10, 2 );
add_filter( 'user_has_cap', __NAMESPACE__ . '\set_pattern_caps' );
add_filter( 'posts_orderby', __NAMESPACE__ . '\filter_orderby_locale', 10, 2 );
add_action( 'init', __NAMESPACE__ . '\add_preview_endpoint' );
add_action( 'setup_theme', __NAMESPACE__ . '\setup_preview_theme', 1 );
add_action( 'template_include', __NAMESPACE__ . '\load_pattern_preview', 100 );
add_filter( 'jetpack_sitemap_post_types', __NAMESPACE__ . '\jetpack_sitemap_post_types' );

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
			'supports'        => array( 'title', 'editor', 'author', 'custom-fields', 'revisions', 'wporg-internal-notes', 'wporg-log-notes' ),
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
			'query_var' => 'pattern-categories',
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
			'default'           => 1200,
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
		'wpop_wp_version',
		array(
			'type'              => 'string',
			'description'       => 'The earliest WordPress version compatible with this pattern.',
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

	register_post_meta(
		POST_TYPE,
		'wpop_contains_block_types',
		array(
			'type'              => 'string',
			'description'       => 'A list of block types used in this pattern',
			'single'            => true,
			'default'           => '',
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
				$slugs = array_map( 'sanitize_title', $slugs );
				$slugs = array_diff( $slugs, [ 'featured' ] );
				$slugs = array_values( $slugs );

				return $slugs;
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
			'get_callback' => function( $response_data ) {
				$pattern = get_post( $response_data['id'] );
				return decode_pattern_content( $pattern->post_content );
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
					'name'   => esc_html( get_the_author_meta( 'display_name', $post['author'] ) ),
					'url'    => esc_url( home_url( '/author/' . get_the_author_meta( 'user_nicename', $post['author'] ) ) ),
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

	register_rest_field(
		POST_TYPE,
		'unlisted_reason',
		array(
			'get_callback' => function() {
				$reasons = wp_get_object_terms( get_the_ID(), FLAG_REASON );
				if ( count( $reasons ) > 0 ) {
					$reason = array_shift( $reasons );
					return array(
						'term_id' => absint( $reason->term_id ),
						'name' => esc_attr( $reason->name ),
						'slug' => esc_attr( $reason->slug ),
						'description' => wp_kses_post( $reason->description ),
					);
				}

				return array();
			},
			'schema' => array(
				'type'  => 'object',
				'properties' => array(
					'term_id' => array(
						'type'  => 'number',
					),
					'name' => array(
						'type'  => 'string',
					),
					'slug' => array(
						'type'  => 'string',
					),
					'description' => array(
						'type'  => 'string',
					),
				),
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
 * Do things when certain status transitions happen.
 *
 * @param string   $new_status
 * @param string   $old_status
 * @param \WP_Post $post
 *
 * @return void
 */
function status_transitions( $new_status, $old_status, $post ) {
	if ( POST_TYPE !== get_post_type( $post ) ) {
		return;
	}

	// If a pattern gets relisted, remove the reason that it was originally unlisted.
	if ( UNLISTED_STATUS === $old_status && UNLISTED_STATUS !== $new_status ) {
		wp_delete_object_term_relationships( $post->ID, array( FLAG_REASON ) );
	}
}

/**
 * Given a post ID, parse out the block types and update the `wpop_contains_block_types` meta field.
 *
 * @param int $pattern_id Pattern ID.
 */
function update_contains_block_types_meta( $pattern_id ) {
	$pattern    = get_post( $pattern_id );
	$blocks     = parse_blocks( $pattern->post_content );
	$all_blocks = _flatten_blocks( $blocks );

	// Get the list of block names and convert it to a single string.
	$block_names = wp_list_pluck( $all_blocks, 'blockName' );
	$block_names = array_filter( $block_names ); // Filter out null values (extra line breaks).
	$block_names = array_unique( $block_names );
	sort( $block_names );
	$used_blocks = implode( ',', $block_names );

	update_post_meta( $pattern_id, 'wpop_contains_block_types', $used_blocks );
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
		throw new Error( 'You need to run `npm run start:directory` or `npm run build:directory` for the Pattern Directory.' );
	}

	$script_asset = require $script_asset_path;
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

/**
 * Filter the collection parameters:
 * - set a new default for per_page.
 * - add a new parameter, `author_name`, for a user's nicename slug.
 * - add a new parameter, `curation`, to filter between curated, community, and all patterns.
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

	$query_params['curation'] = array(
		'description' => __( 'Limit result to either curated core, community, or all patterns.', 'wporg-patterns' ),
		'type'        => 'string',
		'default'     => 'all',
		'enum'        => array(
			'all',
			'core',
			'community',
		),
	);

	if ( isset( $query_params['orderby'] ) ) {
		$query_params['orderby']['enum'][] = 'favorite_count';
	}

	$query_params['wp-version'] = array(
		'description' => __( 'The version of the requesting site, used to filter out newer patterns.', 'wporg-patterns' ),
		'type'        => 'string',
	);

	$query_params['allowed_blocks'] = array(
		'description' => __( 'Filter the request to only return patterns with blocks on this list.', 'wporg-patterns' ),
		'type'        => 'array',
		'items'       => array(
			'type' => 'string',
		),
	);

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

	// If `curation` is passed and either `core` or `community`, we should
	// filter the result. If `curation=all`, no filtering is needed.
	if ( isset( $request['curation'] ) ) {
		if ( 'core' === $request['curation'] ) {
			// Patterns with the core keyword.
			$args['tax_query']['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => 'core',
				'operator' => 'IN',
			);
		} else if ( 'community' === $request['curation'] ) {
			// Patterns without the core keyword.
			$args['tax_query']['core_keyword'] = array(
				'taxonomy' => 'wporg-pattern-keyword',
				'field'    => 'slug',
				'terms'    => 'core',
				'operator' => 'NOT IN',
			);
		}
	}

	$orderby = $request->get_param( 'orderby' );
	if ( 'favorite_count' === $orderby ) {
		$args['orderby'] = 'meta_value_num';
		$args['meta_key'] = 'wporg-pattern-favorites';
	}

	// Use the passed-in version information to skip over any patterns that
	// require newer block features.
	// See https://github.com/WordPress/gutenberg/issues/45179.
	$version = $request->get_param( 'wp-version' );
	if ( $version && preg_match( '/^\d+\.\d+/', $version, $matches ) ) {
		// $version is the full WP version, for example `6.0.2` or `6.2-alpha-54642-src`.
		// Parse out just the major version section, `6.0` or `6.2`, respectively,
		// so that the math comparison works.
		$major_version = $matches[0];
		$args['meta_query']['version'] = array(
			// Fetch patterns with no version info, or only those with a lower
			// or equal version.
			'relation' => 'OR',
			array(
				'key'     => 'wpop_wp_version',
				'compare' => '<=',
				'value'   => $major_version,
			),
			array(
				'key'     => 'wpop_wp_version',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	$allowed_blocks = $request->get_param( 'allowed_blocks' );
	if ( $allowed_blocks ) {
		// Only return a pattern if all contained blocks are in the allowed blocks list.
		$args['meta_query']['allowed_blocks'] = array(
			'key'     => 'wpop_contains_block_types',
			'compare' => 'REGEXP',
			'value'   => '^((' . implode( '|', $allowed_blocks ) . '),?)+$',
		);
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

/**
 * Set up the `view` endpoint.
 *
 * Technically this applies to posts too, but this is easier than a custom EP mask.
 */
function add_preview_endpoint() {
	add_rewrite_endpoint( 'view', EP_PERMALINK );
}

/**
 * When viewing a `view` page, set up the preview theme.
 *
 * This should switch the theme to twentytwentyone, with a white background,
 * and inject the image placeholder workaround.
 */
function setup_preview_theme() {
	// query_vars are not set yet, so just check the URL.
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

	// Match pretty & non-pretty permalinks for unpublished patterns.
	if ( preg_match( '#/view/$#', $request_uri ) || preg_match( '#[?&]view=[1|true]#', $request_uri ) ) {
		add_filter( 'show_admin_bar', '__return_false', 2000 );

		add_filter( 'template', function() {
			if ( 'local' === wp_get_environment_type() ) {
				return 'twentytwentythree';
			} else {
				return 'core/twentytwentythree';
			}
		} );

		add_filter( 'stylesheet', function() {
			if ( 'local' === wp_get_environment_type() ) {
				return 'twentytwentythree';
			} else {
				return 'core/twentytwentythree';
			}
		} );

		add_filter( 'wp_enqueue_scripts', function() {
			wp_deregister_style( 'wp4-styles' );
			wp_deregister_style( 'wporg-global-header-footer' );
		}, 201 );

		add_filter( 'render_block_core/gallery', __NAMESPACE__ . '\inject_placeholder_svg', 10, 2 );
		add_filter( 'render_block_core/image', __NAMESPACE__ . '\inject_placeholder_svg', 10, 2 );
		add_filter( 'render_block_core/media-text', __NAMESPACE__ . '\inject_placeholder_svg', 10, 2 );
		add_filter( 'render_block_core/video', __NAMESPACE__ . '\inject_placeholder_svg', 10, 2 );
		add_filter( 'render_block_core/site-logo', __NAMESPACE__ . '\inject_placeholder_svg', 10, 2 );
	}
}

/**
 * Inject the placehodler SVG if we find an empty media block.
 *
 * @param string $block_content The block content.
 * @param array  $block         The full block, including name and attributes.
 * @return string The updated block content.
 */
function inject_placeholder_svg( $block_content, $block ) {
	$svg = '<svg fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60" preserveAspectRatio="none">';
	$svg .= '<rect width="60" height="60" fill="currentColor" fill-opacity="0.1" />';
	$svg .= '<path vector-effect="non-scaling-stroke" d="M60 60 0 0" stroke="currentColor" stroke-width="1" stroke-opacity="0.25" />';
	$svg .= '</svg>';

	// Image block, find img without `src` or with wmark.png (logo), replace with svg.
	if ( preg_match( '/<img([^>]*)\/?>/', $block_content, $match ) ) {
		if ( ! str_contains( $match[1], 'src=' ) || str_contains( $match[1], 'wmark.png' ) ) {
			$new_content = str_replace( '<svg ', '<svg ' . $match[1], $svg );
			$block_content = str_replace( $match[0], $new_content, $block_content );
			return $block_content;
		}
	}

	// Media & Text block, video block.
	if ( 'core/media-text' === $block['blockName'] || 'core/video' === $block['blockName'] ) {
		// Find empty `<figure …></figure>`, inject svg into figure.
		if ( preg_match( '/(<figure[^>]*>)(<\/figure>)/', $block_content, $match ) ) {
			$new_content = $match[1] . $svg . $match[2];
			$block_content = str_replace( $match[0], $new_content, $block_content );
		}
	}

	// Gallery, find empty `<figure …></figure>`, inject 3 fake image blocks into figure.
	if ( 'core/gallery' === $block['blockName'] && preg_match( '/(<figure[^>]*>)(<\/figure>)/', $block_content, $match ) ) {
		$image = '<figure class="wp-block-image">' . $svg . '</figure>';
		$new_content = $match[1] . str_repeat( $image, 3 ) . $match[2];
		$block_content = str_replace( $match[0], $new_content, $block_content );
	}

	return $block_content;
}

/**
 * If this is the `view` query, use our version of the `template-canvas.php`.
 */
function load_pattern_preview( $template ) {
	global $wp_query;

	if ( ! isset( $wp_query->query_vars['view'] ) ) {
		return $template;
	}

	return dirname( __DIR__ ) . '/views/view.php';
}

/**
 * Intercept the post object and decode the content.
 */
add_action(
	'the_post',
	function( $post ) {
		$post->post_content = decode_pattern_content( $post->post_content );
	}
);

/**
 * Process post content, replacing broken encoding & removing refs.
 *
 * Some image URLs have &s, which are double-encoded and sanitized to become malformed,
 * for example, `https://img.rawpixel.com/s3fs-private/rawpixel_images/website_content/a010-markuss-0964.jpg?w=1200\u0026amp;h=1200\u0026amp;fit=clip\u0026amp;crop=default\u0026amp;dpr=1\u0026amp;q=75\u0026amp;vib=3\u0026amp;con=3\u0026amp;usm=15\u0026amp;cs=srgb\u0026amp;bg=F4F4F3\u0026amp;ixlib=js-2.2.1\u0026amp;s=7d494bd5db8acc2a34321c15ed18ace5`.
 *
 * @param string $content The raw post content.
 *
 * @return string
 */
function decode_pattern_content( $content ) {
	// Sometimes the initial `\` is missing, so look for both versions.
	$content = str_replace( [ '\u0026amp;', 'u0026amp;' ], '&', $content );
	// Remove `ref` from all content.
	$content = preg_replace( '/"ref":\d+,?/', '', $content );
	return $content;
}

/**
 * Given a post, return the unlisted reason (if one exists).
 *
 * @param int $post_id Post ID.
 *
 * @return string
 */
function get_pattern_unlisted_reason( $post_id ) {
	$reasons = wp_get_object_terms( get_the_ID(), FLAG_REASON );
	if ( count( $reasons ) > 0 ) {
		$reason = array_shift( $reasons );
		return $reason->description;
	}

	return '';
}

/**
 * Allow the Patterns to be included in the Jetpack Sitemaps.
 *
 * @param array $post_types The post types to include.
 *
 * @return array
 */
function jetpack_sitemap_post_types( $post_types ) {
	$post_types[] = POST_TYPE;

	return $post_types;
}
