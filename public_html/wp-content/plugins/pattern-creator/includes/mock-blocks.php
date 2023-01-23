<?php
/**
 * Mock dynamic blocks that use site content.
 */

namespace WordPressdotorg\Pattern_Creator\MockBlocks;

use WP_Block_Supports;

defined( 'WPINC' ) || die();

add_action( 'render_block_core/archives', __NAMESPACE__ . '\render_archives', 10, 3 );
add_action( 'render_block_core/latest-comments', __NAMESPACE__ . '\render_latest_comments', 10, 3 );
add_filter( 'block_core_navigation_render_fallback', __NAMESPACE__ . '\provide_fallback_nav_items' );
add_filter( 'render_block_data', __NAMESPACE__ . '\update_block_data' );
add_filter( 'get_custom_logo', __NAMESPACE__ . '\provide_mock_logo' );

/**
 * Mock the Archives block.
 *
 * @param string   $block_content  The block content about to be appended.
 * @param array    $block          The full block, including name and attributes.
 * @param WP_Block $block_instance The block instance.
 * @return string
 */
function render_archives( $block_content, $block, $block_instance ) {
	$show_post_count = ! empty( $block_instance->attributes['showPostCounts'] );
	$show_dropdown = ! empty( $block_instance->attributes['displayAsDropdown'] );
	$dropdown_id = esc_attr( uniqid( 'wp-block-archives-' ) );
	$class = '';

	$dates = array();
	$current = strtotime( '12 months ago' );
	$last = time();
	while ( $current <= $last ) {
		$dates[] = wp_date( 'F Y', $current );
		$current = strtotime( 'next month', $current );
	}

	if ( $show_dropdown ) {
		$title       = __( 'Archives', 'wporg-patterns' );
		$label = __( 'Select Month', 'wporg-patterns' );
		$archives = '';

		foreach ( $dates as $date ) {
			if ( $show_post_count ) {
				$archives .= sprintf( '<option>%1$s (%2$s)</option>', $date, rand( 5, 25 ) );
			} else {
				$archives .= sprintf( '<option>%s</option>', $date );
			}
		}

		$block_content = '<label for="' . $dropdown_id . '">' . $title . '</label><select id="' . $dropdown_id . '" name="archive-dropdown"><option value="">' . $label . '</option>' . $archives . '</select>';

		$class .= ' wp-block-archives-dropdown';
		$classnames = esc_attr( $class );

		// Required to prevent `block_to_render` from being null in `get_block_wrapper_attributes`.
		$parent = WP_Block_Supports::$block_to_render;
		WP_Block_Supports::$block_to_render = $block;
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );
		WP_Block_Supports::$block_to_render = $parent;

		return sprintf(
			'<div %1$s>%2$s</div>',
			$wrapper_attributes,
			$block_content
		);
	} else {
		$archives = '';

		foreach ( $dates as $date ) {
			if ( $show_post_count ) {
				$archives .= sprintf( '<li><a href="">%1$s</a> (%2$s)</li>', $date, rand( 5, 25 ) );
			} else {
				$archives .= sprintf( '<li><a href="">%s</a></li>', $date );
			}
		}

		$class .= ' wp-block-archives-list';
		$classnames = esc_attr( $class );
		$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $classnames ) );

		return sprintf(
			'<ul %1$s>%2$s</ul>',
			$wrapper_attributes,
			$archives
		);
	}
}

/**
 * Mock the Latest Comments block.
 *
 * @param string   $block_content  The block content about to be appended.
 * @param array    $block          The full block, including name and attributes.
 * @param WP_Block $block_instance The block instance.
 * @return string
 */
function render_latest_comments( $block_content, $block, $block_instance ) {
	$attributes = $block_instance->attributes;
	$list_items_markup = '';

	/* Note: This is not translated (for now) because the post content is also not translated. */
	$comments = array(
		[
			'author' => 'Noah',
			'post_title' => 'Jupiter',
			'date' => strtotime( '5 days ago' ),
			'content' => 'Since its orbital revolution occupies nearly twelve years, Jupiter comes back into opposition with the Sun every 399 days.',
		],
		[
			'author' => 'Sabrina',
			'post_title' => 'Jupiter',
			'date' => strtotime( '1 week ago' ),
			'content' => 'Most conspicuous upon this globe are the larger or smaller bands or markings (gray and white, sometimes tinted yellow, or of a maroon or chocolate hue) by which its surface is streaked, particularly in the vicinity of the equator.',
		],
		[
			'author' => 'Yvonne',
			'post_title' => 'The November Meteors',
			'date' => strtotime( '2 weeks ago' ),
			'content' => 'One or two unknown planets, some wandering comets, and swarms of meteors, doubtless traverse those unknown spaces, but all invisible to us.',
		],
	);

	$comments = array_slice( $comments, 0, min( 3, $attributes['commentsToShow'] ) );
	foreach ( $comments as $comment ) {
		$list_items_markup .= '<li class="wp-block-latest-comments__comment">';
		if ( $attributes['displayAvatar'] ) {
			$list_items_markup .= get_avatar( null, 48, '', '', [ 'class' => 'wp-block-latest-comments__comment-avatar' ] );
		}

		$list_items_markup .= '<article>';
		$list_items_markup .= '<footer class="wp-block-latest-comments__comment-meta">';

		$author_markup = '<span class="wp-block-latest-comments__comment-author">' . $comment['author'] . '</span>';
		$post_title = '<a class="wp-block-latest-comments__comment-link" href="#">' . $comment['post_title'] . '</a>';

		$list_items_markup .= sprintf(
			/* translators: 1: author name, 2: post title related to this comment */
			__( '%1$s on %2$s', 'wporg-patterns' ),
			$author_markup,
			$post_title
		);

		if ( $attributes['displayDate'] ) {
			$list_items_markup .= sprintf(
				'<time datetime="%1$s" class="wp-block-latest-comments__comment-date">%2$s</time>',
				esc_attr( wp_date( 'c', $comment['date'] ) ),
				wp_date( get_option( 'date_format' ), $comment['date'] )
			);
		}
		$list_items_markup .= '</footer>';
		if ( $attributes['displayExcerpt'] ) {
			$list_items_markup .= '<div class="wp-block-latest-comments__comment-excerpt">' . $comment['content'] . '</div>';
		}
		$list_items_markup .= '</article></li>';
	}

	$classnames = array();
	if ( $attributes['displayAvatar'] ) {
		$classnames[] = 'has-avatars';
	}
	if ( $attributes['displayDate'] ) {
		$classnames[] = 'has-dates';
	}
	if ( $attributes['displayExcerpt'] ) {
		$classnames[] = 'has-excerpts';
	}

	// Required to prevent `block_to_render` from being null in `get_block_wrapper_attributes`.
	$parent = WP_Block_Supports::$block_to_render;
	WP_Block_Supports::$block_to_render = $block;
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => implode( ' ', $classnames ) ) );
	WP_Block_Supports::$block_to_render = $parent;

	return sprintf(
		'<ol %1$s>%2$s</ol>',
		$wrapper_attributes,
		$list_items_markup
	);
}

/**
 * Helper function to attach some filters only when necessary.
 */
function attach_site_data_filters() {
	add_filter( 'pre_option_blogdescription', __NAMESPACE__ . '\replace_site_info', 10, 3 );
	add_filter( 'pre_option_blogname', __NAMESPACE__ . '\replace_site_info', 10, 3 );
}

/**
 * Helper function to remove filters so they don't affect aything else.
 *
 * @see attach_site_data_filters.
 */
function remove_site_data_filters() {
	remove_filter( 'pre_option_blogdescription', __NAMESPACE__ . '\replace_site_info', 10, 3 );
	remove_filter( 'pre_option_blogname', __NAMESPACE__ . '\replace_site_info', 10, 3 );
}

/**
 * Filter site options to return placeholder content for title & tagline.
 *
 * These placeholders should be the same as returned in `src/api-middleware/mock-site-data.js`.
 *
 * @param mixed  $value   Value to return.
 * @param string $option  Option name.
 * @param mixed  $default The fallback value to return if the option does not exist.
 *                           Default false.
 * @return string
 */
function replace_site_info( $value, $option, $default ) {
	switch ( $option ) {
		case 'blogdescription':
			return __( 'Site Tagline placeholder', 'wporg-patterns' );
		case 'blogname':
			return __( 'Site Title placeholder', 'wporg-patterns' );
	}
	return $value;
}

/**
 * Provide custom links as default for empty Navigation blocks.
 *
 * @param array[] $fallback_blocks default fallback blocks provided by the default block mechanic.
 * @return array[]
 */
function provide_fallback_nav_items( $fallback_blocks ) {
	return array(
		array(
			'blockName' => 'core/navigation-link',
			'attrs' => array(
				'label' => 'Home',
				'url' => '#',
				'kind' => 'custom',
				'isTopLevelLink' => true,
			),
		),
		array(
			'blockName' => 'core/navigation-link',
			'attrs' => array(
				'label' => 'About',
				'url' => '#',
				'kind' => 'custom',
				'isTopLevelLink' => true,
			),
		),
		array(
			'blockName' => 'core/navigation-link',
			'attrs' => array(
				'label' => 'Contact',
				'url' => '#',
				'kind' => 'custom',
				'isTopLevelLink' => true,
			),
		),
	);
}

/**
 * Remove the `ref` from the navigation block to ensure it renders the fallback.
 *
 * @param array $parsed_block The block being rendered.
 * @return array Updated block data.
 */
function update_block_data( $parsed_block ) {
	if ( 'core/navigation' === $parsed_block['blockName'] ) {
		unset( $parsed_block['attrs']['ref'] );
	}

	return $parsed_block;
}

/**
 * Replace the custom logo output with the WordPress W.
 * This serves to provide a placeholder for the Site Logo block.
 *
 * @param string $html Custom logo HTML output.
 */
function provide_mock_logo( $html ) {
	return sprintf(
		'<span class="custom-logo-link"><img src="%s" class="custom-logo" alt="%s"></span>',
		'https://s.w.org/images/wmark.png',
		__( 'Site logo', 'wporg-patterns' )
	);
}
