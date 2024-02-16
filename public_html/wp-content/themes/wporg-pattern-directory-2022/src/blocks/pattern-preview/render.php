<?php

$url = add_query_arg( 'view', true, get_permalink( $block->context['postId'] ) );

// Initial state to pass to Interactivity API.
$init_state = [
	'previewWidth' => 1200,
	'previewHeight' => 200,
	'iframeWidth' => 1200,
];
$encoded_state = wp_json_encode( $init_state );

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/preview"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-init="actions.handleOnResize"
	data-wp-on-window--resize="actions.handleOnResize"
>
	<div class="wp-block-wporg-pattern-preview__controls">
		<select
			data-wp-on--change="actions.onWidthChange"
			data-wp-bind--value="context.previewWidth"
		>
			<option value="1200"><?php _e( 'Full (1200px)', 'wporg-patterns' ); ?></option>
			<option value="960"><?php _e( 'Default (960px)', 'wporg-patterns' ); ?></option>
			<option value="600"><?php _e( 'Medium (600px)', 'wporg-patterns' ); ?></option>
			<option value="480"><?php _e( 'Narrow (480px)', 'wporg-patterns' ); ?></option>
		</select>
	</div>
	<div
		class="wp-block-wporg-pattern-preview__frame"
		style="overflow:hidden;"
		data-wp-style--height="state.previewHeightCSS"
		tabIndex="-1"
	>
		<iframe
			title=<?php _e( 'Pattern Preview', 'wporg-patterns' ); ?>
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
</div>
