<?php

use function WordPressdotorg\Theme\Pattern_Directory_2024\get_pattern_preview_url;

if ( ! isset( $block->context['postId'] ) ) {
	return '';
}

$view_url = get_pattern_preview_url( $block->context['postId'] );

// Initial state to pass to Interactivity API.
$init_state = [
	'url' => $view_url,
	'previewWidth' => 1200,
	'previewHeight' => 200,
];
$encoded_state = wp_json_encode( $init_state );

// Remove the nested context for child blocks, so that it uses this context.
$p = new WP_HTML_Tag_Processor( $content );
$p->next_tag( 'div' );
$p->remove_attribute( 'data-wp-interactive' );
$p->remove_attribute( 'data-wp-context' );
$content = $p->get_updated_html();

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/preview"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-init="actions.handleOnResize"
	data-wp-on-window--resize="actions.handleOnResize"
>
	<?php echo $content; ?>

	<section class="wporg-pattern-view-control__controls wp-block-buttons" aria-label="<?php esc_attr_e( 'Preview width', 'wporg-patterns' ); ?>">
		<div class="wp-block-button is-style-toggle is-small">
			<button
				class="wp-block-button__link wp-element-button"
				data-wp-bind--aria-pressed="state.isWidth1200"
				data-wp-on--click="actions.onWidthChange"
				data-width="1200"
			>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="-100 -1060 1160 1160" width="36" height="36" aria-hidden="true" focusable="false"><path d="M320-120v-80h80v-80H160q-33 0-56.5-23.5T80-360v-400q0-33 23.5-56.5T160-840h640q33 0 56.5 23.5T880-760v400q0 33-23.5 56.5T800-280H560v80h80v80H320ZM160-360h640v-400H160v400Zm0 0v-400 400Z"/></svg>
				<span><?php _e( 'Full', 'wporg-patterns' ); ?></span>
			</button>
		</div>
		<div class="wp-block-button is-style-toggle is-small">
			<button
				class="wp-block-button__link wp-element-button"
				data-wp-bind--aria-pressed="state.isWidth960"
				data-wp-on--click="actions.onWidthChange"
				data-width="960"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36" aria-hidden="true" focusable="false"><path d="M20.5 16h-.7V8c0-1.1-.9-2-2-2H6.2c-1.1 0-2 .9-2 2v8h-.7c-.8 0-1.5.7-1.5 1.5h20c0-.8-.7-1.5-1.5-1.5zM5.7 8c0-.3.2-.5.5-.5h11.6c.3 0 .5.2.5.5v7.6H5.7V8z"></path></svg>
				<span><?php _e( 'Default', 'wporg-patterns' ); ?></span>
			</button>
		</div>
		<div class="wp-block-button is-style-toggle is-small">
			<button
				class="wp-block-button__link wp-element-button"
				data-wp-bind--aria-pressed="state.isWidth600"
				data-wp-on--click="actions.onWidthChange"
				data-width="600"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36" aria-hidden="true" focusable="false"><path d="M17 4H7c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm.5 14c0 .3-.2.5-.5.5H7c-.3 0-.5-.2-.5-.5V6c0-.3.2-.5.5-.5h10c.3 0 .5.2.5.5v12zm-7.5-.5h4V16h-4v1.5z"></path></svg>
				<span><?php _e( 'Medium', 'wporg-patterns' ); ?></span>
			</button>
		</div>
		<div class="wp-block-button is-style-toggle is-small">
			<button
				class="wp-block-button__link wp-element-button"
				data-wp-bind--aria-pressed="state.isWidth480"
				data-wp-on--click="actions.onWidthChange"
				data-width="480"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36" aria-hidden="true" focusable="false"><path d="M15 4H9c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm.5 14c0 .3-.2.5-.5.5H9c-.3 0-.5-.2-.5-.5V6c0-.3.2-.5.5-.5h6c.3 0 .5.2.5.5v12zm-4.5-.5h2V16h-2v1.5z"></path></svg>
				<span><?php _e( 'Narrow', 'wporg-patterns' ); ?></span>
			</button>
		</div>
	</section>
</div>
