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
	'isControlled' => true,
];
$encoded_state = wp_json_encode( $init_state );

// Remove the nested context for child blocks, so that it uses this context.
$p = new WP_HTML_Tag_Processor( $content );
$p->next_tag( 'div' );
$p->remove_attribute( 'data-wp-interactive' );
$p->remove_attribute( 'data-wp-context' );
$content = $p->get_updated_html();

$html_id = wp_unique_id( 'pattern-preview-help-' );

?>
<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/preview"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-class--is-mobile-view="state.isWidthNarrow"
	data-wp-class--is-dragging="state.isDrag"
	data-wp-on-window--mousemove="actions.onDrag"
	data-wp-on-window--mouseup="actions.onDragEnd"
>
	<section class="wporg-pattern-view-control__controls wp-block-buttons" aria-label="<?php esc_attr_e( 'Preview width', 'wporg-patterns' ); ?>">
		<div class="wp-block-button is-style-toggle is-small">
			<button
				class="wp-block-button__link wp-element-button"
				data-wp-bind--aria-pressed="state.isWidthWide"
				data-wp-on--click="actions.onWidthChange"
				data-width="1200"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36" aria-hidden="true" focusable="false"><path d="M20.5 16h-.7V8c0-1.1-.9-2-2-2H6.2c-1.1 0-2 .9-2 2v8h-.7c-.8 0-1.5.7-1.5 1.5h20c0-.8-.7-1.5-1.5-1.5zM5.7 8c0-.3.2-.5.5-.5h11.6c.3 0 .5.2.5.5v7.6H5.7V8z"></path></svg>
				<span><?php echo esc_attr_x( 'Wide', 'pattern preview size toggle', 'wporg-patterns' ); ?></span>
			</button>
		</div>
		<div class="wp-block-button is-style-toggle is-small">
			<button
				class="wp-block-button__link wp-element-button"
				data-wp-bind--aria-pressed="state.isWidthMedium"
				data-wp-on--click="actions.onWidthChange"
				data-width="800"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36" aria-hidden="true" focusable="false"><path d="M17 4H7c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm.5 14c0 .3-.2.5-.5.5H7c-.3 0-.5-.2-.5-.5V6c0-.3.2-.5.5-.5h10c.3 0 .5.2.5.5v12zm-7.5-.5h4V16h-4v1.5z"></path></svg>
				<span><?php echo esc_attr_x( 'Medium', 'pattern preview size toggle', 'wporg-patterns' ); ?></span>
			</button>
		</div>
		<div class="wp-block-button is-style-toggle is-small">
			<button
				class="wp-block-button__link wp-element-button"
				data-wp-bind--aria-pressed="state.isWidthNarrow"
				data-wp-on--click="actions.onWidthChange"
				data-width="400"
			>
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36" aria-hidden="true" focusable="false"><path d="M15 4H9c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm.5 14c0 .3-.2.5-.5.5H9c-.3 0-.5-.2-.5-.5V6c0-.3.2-.5.5-.5h6c.3 0 .5.2.5.5v12zm-4.5-.5h2V16h-2v1.5z"></path></svg>
				<span><?php echo esc_attr_x( 'Narrow', 'pattern preview size toggle', 'wporg-patterns' ); ?></span>
			</button>
		</div>
	</section>

	<div class="wporg-pattern-preview__drag-container">
		<div class="wporg-pattern-preview__drag-handle">
			<button
				class="wporg-pattern-view-control__drag-handle is-left"
				aria-label="<?php esc_attr_e( 'Drag to resize', 'wporg-patterns' ); ?>"
				aria-describedby="<?php echo esc_attr( $html_id ); ?>-left"
				data-direction="left"
				data-wp-on--keydown="actions.onLeftKeyDown"
				data-wp-on--mousedown="actions.onDragStart"
			></button>
		</div>
		<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content from child blocks.
			echo $content;
		?>
		<div class="wporg-pattern-preview__drag-handle">
			<button
				class="wporg-pattern-view-control__drag-handle is-right"
				aria-label="<?php esc_attr_e( 'Drag to resize', 'wporg-patterns' ); ?>"
				aria-describedby="<?php echo esc_attr( $html_id ); ?>-right"
				data-direction="right"
				data-wp-on--keydown="actions.onRightKeyDown"
				data-wp-on--mousedown="actions.onDragStart"
			></button>
		</div>
	</div>

	<span id="<?php echo esc_attr( $html_id ); ?>-left" hidden>
		<?php esc_attr_e( 'Drag or use arrow keys to resize the pattern preview. Left to make larger, right to make smaller.', 'wporg-patterns' ); ?>
	</span>
	<span id="<?php echo esc_attr( $html_id ); ?>-right" hidden>
		<?php esc_attr_e( 'Drag or use arrow keys to resize the pattern preview. Right to make larger, left to make smaller.', 'wporg-patterns' ); ?>
	</span>
</div>
