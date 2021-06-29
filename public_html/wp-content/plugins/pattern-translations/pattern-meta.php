<?php

namespace WordPressdotorg\Pattern_Translations;

/**
 * Add custom taxonomy for metadata associated with a pattern.
 *
 * This allows us to flag patterns as premium, define viewport width,
 * and any other metadata that will be used when registering the pattern,
 * but that should not be visible to the end user.
 */
function register_pattern_meta_taxonomy() {
	if ( ! has_blog_sticker( 'block-patterns-source-site' ) ) {
		return;
	}

	if ( taxonomy_exists( 'pattern_meta' ) ) {
		return;
	}

	$labels = [
		'name'                       => 'Pattern Meta',
		'singular_name'              => 'Pattern Meta',
		'search_items'               => 'Search Pattern Meta',
		'popular_items'              => 'Popular Pattern Meta',
		'all_items'                  => 'All Pattern Meta',
		'parent_item'                => 'Parent Pattern Meta',
		'parent_item_colon'          => 'Parent Pattern Meta:',
		'edit_item'                  => 'Edit Pattern Meta',
		'view_item'                  => 'View Pattern Meta',
		'update_item'                => 'Update Pattern Meta',
		'add_new_item'               => 'Add New Pattern Meta',
		'new_item_name'              => 'New Pattern Meta',
		'separate_items_with_commas' => 'Separate Pattern Meta tags with commas',
		'add_or_remove_items'        => 'Add or remove Pattern Meta',
		'choose_from_most_used'      => 'Choose from the most used Pattern Meta',
		'not_found'                  => 'No Pattern Meta found',
		'no_terms'                   => 'No Pattern Meta',
		'items_list_navigation'      => 'Pattern Meta',
		'items_list'                 => 'Pattern Meta',
		'most_used'                  => 'Most Used',
		'back_to_items'              => 'Back to ',
		'menu_name'                  => 'Pattern Meta',
	];

	register_taxonomy(
		'pattern_meta',
		[ 'page', 'post' ],
		[
			'labels'            => $labels,
			'description'       => 'Meta data and flags for block patterns.',
			'hierarchical'      => false,
			'public'            => true,
			'show_in_rest'      => true,
			'rest_base'         => 'pattern_meta',
			'show_tagcloud'     => false,
			'show_admin_column' => true,
			'show_ui'           => true,
			'capabilities'      => [
				'manage_terms' => 'manage_post_tags',
				'edit_terms'   => 'edit_post_tags',
				'delete_terms' => 'delete_post_tags',
				'assign_terms' => 'assign_post_tags',
			],
		]
	);
}

/**
 * Unregister custom taxonomy for metadata associated with a pattern.
 *
 * This allows us to flag patterns as premium, define viewport width,
 * and any other metadata that will be used when registering the pattern,
 * but that should not be visible to the end user.
 */
function unregister_pattern_meta_taxonomy() {
	unregister_taxonomy( 'pattern_meta' );
}

/**
 * Iterate over a block and its inner blocks, to retrieve all block names.
 */
function get_block_names( $block ) {
	$block_names   = [];
	$block_names[] = str_replace( 'jetpack/', '', $block['blockName'] );
	foreach ( $block['innerBlocks'] as $inner_block ) {
		$block_names = array_merge( $block_names, get_block_names( $inner_block ) );
	}
	return $block_names;
}

/**
 * Parse post_content for an inserted post, and based on that content,
 * automatically add particular pattern_meta terms to the post.
 *
 * This allows us to flag particular features of a pattern, such
 * as is_premium.
 */
function add_pattern_meta_terms( $post_id, $post ) {
	if ( ! taxonomy_exists( 'pattern_meta' ) ) {
		return;
	}

	if ( in_array( $post->post_type, [ 'post', 'page' ], true ) && ! empty( $post->post_content ) ) {

		// Handle `is_premium` flag by checking for the existence of a paid feature matching one
		// of the block names included in the post content.

		$paid_features = \Store_Product_List::get_feature_list();

		$blocks      = parse_blocks( $post->post_content );
		$block_names = [];
		foreach ( $blocks as $block ) {
			$block_names = array_merge( $block_names, get_block_names( $block ) );
		}

		$block_names = array_unique( array_filter( $block_names ) );

		foreach ( $paid_features as $paid_feature ) {
			if ( 'core/cover' === $paid_feature['product_slug'] ) {
				// Skip core/cover feature, otherwise all patterns with the
				// core/cover block will be marked as premium.
				continue;
			}

			if ( in_array( $paid_feature['product_slug'], $block_names, true ) ) {
				// Assign the `is_premium` `pattern_meta` term to the post, ensuring that the term is appended.
				wp_set_object_terms( $post_id, 'is_premium', 'pattern_meta', true );
			}
		}
	}
};

/**
 * Register the callback for automatically adding pattern meta terms
 * to posts based on the post_content.
 */
function register_automatic_pattern_meta_insertion_hooks() {
	if ( ! has_blog_sticker( 'block-patterns-source-site' ) ) {
		return;
	}
	add_action( 'wp_insert_post', __NAMESPACE__ . '\add_pattern_meta_terms', 10, 2 );
}

/**
 * Unregister the callback for automatically adding pattern meta terms
 * to posts based on the post_content.
 */
function unregister_automatic_pattern_meta_insertion_hooks() {
	if ( ! has_blog_sticker( 'block-patterns-source-site' ) ) {
		return;
	}
	remove_action( 'wp_insert_post', __NAMESPACE__ . '\add_pattern_meta_terms', 10, 2 );
}
