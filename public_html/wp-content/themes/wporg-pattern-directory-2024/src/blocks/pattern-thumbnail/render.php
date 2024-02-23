<?php

use function WordPressdotorg\Theme\Pattern_Directory_2024\get_pattern_preview_url;

if ( ! isset( $block->context['postId'] ) ) {
	return '';
}
$post_id = $block->context['postId'];

$view_url = get_pattern_preview_url( $post_id );
$has_link = isset( $attributes['isLink'] ) && true == $attributes['isLink'];
$is_lazyload = isset( $attributes['lazyLoad'] ) && true === $attributes['lazyLoad'];

$viewport_width = get_post_meta( $post_id, 'wpop_viewport_width', true );

if ( ! $viewport_width ) {
	$viewport_width = 1200;
}

$cache_key = '20240223'; // To break out of cached image.

$view_url = add_query_arg( 'v', $cache_key, $view_url );
$url = add_query_arg(
	array(
		'scale' => 2,
		'w' => 1100,
		'vpw' => $viewport_width,
		'vph' => 300, // Smaller than the vast majority of patterns to avoid whitespace.
		'screen_height' => 3600, // Max height of a screenshot.
	),
	'https://s0.wp.com/mshots/v1/' . urlencode( $view_url ),
);

// Initial state to pass to Interactivity API.
$init_state = [
	'base64Image' => '',
	'src' => esc_url( $url ),
	'alt' => the_title_attribute( array( 'echo' => false ) ),
];
$encoded_state = wp_json_encode( $init_state );

$classname = '';
if ( $has_link ) {
	$classname .= ' is-linked-image';
}

?>
<div
	<?php echo get_block_wrapper_attributes( array( 'class' => $classname ) ); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/thumbnail"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-init="callbacks.init"
	data-wp-class--has-loaded="state.hasLoaded"
	tabIndex="-1"
>
	<?php if ( $has_link ) : ?>
	<a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
	<?php endif; ?>

	<div
		data-wp-class--wporg-pattern-thumbnail__loader="!state.hasLoaded"
		data-wp-class--wporg-pattern-thumbnail__error="state.hasError"
	>
		<img
			data-wp-bind--hidden="!state.base64Image"
			data-wp-bind--alt="context.alt"
			data-wp-bind--src="state.base64Image"
		/>
		<span
			data-wp-bind--hidden="state.base64Image"
			data-wp-text="context.alt"
		></span>
	</div>

	<?php if ( $has_link ) : ?>
	</a>
	<?php endif; ?>
</div>