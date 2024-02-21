<?php

use function WordPressdotorg\Theme\Pattern_Directory_2024\get_pattern_preview_url;

if ( ! isset( $block->context['postId'] ) ) {
	return '';
}

$view_url = get_pattern_preview_url( $block->context['postId'] );
$viewport_width = get_post_meta( $block->context['postId'], 'wpop_viewport_width', true );

if ( ! $viewport_width ) {
	$viewport_width = 1200;
}

// Initial state to pass to Interactivity API.
$init_state = [
	'url' => $view_url,
	'previewWidth' => $viewport_width,
	'previewHeight' => 200,
];
$encoded_state = wp_json_encode( $init_state );

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/preview"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-style--height="state.previewHeightCSS"
	data-wp-init="actions.handleOnResize"
	data-wp-on-window--resize="actions.handleOnResize"
	tabIndex="-1"
>
	<iframe
		title="<?php _e( 'Pattern Preview', 'wporg-patterns' ); ?>"
		tabIndex="-1"
		src="<?php echo esc_url( $view_url ); ?>"
		data-wp-style--width="state.iframeWidthCSS"
		data-wp-style--height="state.iframeHeightCSS"
		data-wp-style--transform="state.transformCSS"
		data-wp-init="actions.onLoad"
		data-wp-watch="actions.updatePreviewHeight"
		style="transform-origin: <?php echo is_rtl() ? 'top right' : 'top left'; ?>;"
	></iframe>
</div>