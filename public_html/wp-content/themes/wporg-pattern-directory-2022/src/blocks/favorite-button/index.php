<?php
/**
 * Block Name: Favorite Button
 * Description: A button showing the current count of favorites, which can toggle favoriting on the current pattern.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2022\Favorite_Button_Block;
use function WordPressdotorg\Pattern_Directory\Favorite\{get_favorite_count, is_favorite};

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type(
		__DIR__ . '/../../../build/blocks/favorite-button',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}

/**
 * Render the block content.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	if ( ! empty( $block->block_type->view_script ) ) {
		wp_enqueue_script( $block->block_type->view_script );
		// Move to footer.
		wp_script_add_data( $block->block_type->view_script, 'group', 1 );
	}
	$variant = $attributes[ 'variant' ] ?? 'default';

	$post_id = $block->context['postId'];
	if ( ! $post_id ) {
		return '';
	}

	$user_id = get_current_user_id();
	
	if ( 'small' === $variant ) {
		$button = get_small_button( $post_id, $user_id );
	} else {
		if ( ! $user_id ) {
			return '';
		}
		$button = get_button( $post_id, $user_id );
	}

	$is_favorite = is_favorite( $post_id, $user_id );
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'is-style-outline',
		)
	);
	return sprintf(
		'<div %1$s data-post-id="%2$s" data-action="%3$s">%4$s</div>',
		$wrapper_attributes,
		esc_attr( $post_id ),
		$is_favorite ? 'remove' : 'add',
		$button
	);
}

function get_button( $post_id, $user_id ) {
	$add_label = __( 'Add to favorites', 'wporg-patterns' );
	$remove_label = __( 'Remove from favorites', 'wporg-patterns' );
	$is_favorite = is_favorite( $post_id, $user_id );

	// Render a disabled button until the JS kicks in.
	$button = '<button class="wp-block-button__link wp-element-button" disabled="disabled">❤️ ';
	$button .= '<span class="wp-block-wporg-favorite-button__label">';
	$button .= $is_favorite ? $remove_label : $add_label;
	$button .= '</span>';
	$button .= '</button>';

	return $button;
}

function get_small_button( $post_id, $user_id ) {
	$add_label = __( 'Add to favorites', 'wporg-patterns' );
	$remove_label = __( 'Remove from favorites', 'wporg-patterns' );
	$is_favorite = is_favorite( $post_id, $user_id );
	$favorite_count = get_favorite_count( $post_id );

	// Render a disabled button until the JS kicks in.
	$button = '<button class="wp-block-wporg-favorite-button__button" disabled="disabled">❤️ ';

	if ( ! $user_id ) {
		$button = '<span class="wp-block-wporg-favorite-button__button" class="">❤️ ';
	}

	$button .= '<span class="wp-block-wporg-favorite-button__count">';
	$button .= '<span class="screen-reader-text">';
	$button .= sprintf(
		_n(
			'Favorited %s times',
			'Favorited %s times',
			$favorite_count,
			'wporg-patterns'
		),
		$favorite_count
	);
	$button .= '</span>';
	$button .= '<span aria-hidden="true">' . $favorite_count . '</span>';
	$button .= '</span>';

	$button .= '<span class="wp-block-wporg-favorite-button__label screen-reader-text">';
	$button .= $is_favorite ? $remove_label : $add_label;
	$button .= '</span>';

	if ( $user_id ) {
		$button .= '</button>';
	} else {
		$button .= '</span>';
	}

	return $button;
}
