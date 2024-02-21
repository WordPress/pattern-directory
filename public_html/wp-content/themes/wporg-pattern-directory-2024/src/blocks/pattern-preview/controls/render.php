<?php

$url = add_query_arg( 'view', true, get_permalink( $block->context['postId'] ) );

// Initial state to pass to Interactivity API.
$init_state = [
	'url' => $url,
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
	<div class="wp-block-wporg-pattern-view-control__controls">
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
	<?php echo $content; ?>
</div>
