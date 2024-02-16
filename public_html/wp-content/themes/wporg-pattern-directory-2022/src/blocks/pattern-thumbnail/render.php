<?php

$url = add_query_arg( 'view', true, get_permalink( $block->context['postId'] ) );

// Initial state to pass to Interactivity API.
$init_state = [
	'previewWidth' => 1200, // @todo This should reflect the viewportWidth property.
	'previewHeight' => 200,
	'iframeWidth' => 600,
];
$encoded_state = wp_json_encode( $init_state );

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/thumbnail"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-style--height="state.previewHeightCSS"
	data-wp-init="actions.handleOnResize"
	data-wp-on-window--resize="actions.handleOnResize"
	tabIndex="-1"
>
	<iframe
		title=<?php _e( 'Pattern Preview', 'wporg-patterns' ); ?>"
		tabIndex="-1"
		src="<?php echo esc_url( $url ); ?>"
		data-wp-style--width="state.iframeWidthCSS"
		data-wp-style--height="state.iframeHeightCSS"
		data-wp-style--transform="state.transformCSS"
		data-wp-on--load="actions.updatePreviewHeight"
		data-wp-watch="actions.updatePreviewHeight"
		style="transform-origin: <?php echo is_rtl() ? 'top right' : 'top left'; ?>;"
	></iframe>
</div>