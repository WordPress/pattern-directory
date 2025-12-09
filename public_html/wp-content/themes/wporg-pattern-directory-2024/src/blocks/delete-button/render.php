<?php

$current_post_id = $block->context['postId'];
if ( ! $current_post_id ) {
	return;
}

// Check if the user has permissions.
if ( ! current_user_can( 'edit_post', $current_post_id ) ) {
	return;
}

// Manually enqueue this script, so that it's available for the interactivity view script.
wp_enqueue_script( 'wp-api-fetch' );

// Initial state to pass to Interactivity API.
$init_state = [
	'postId' => $current_post_id,
	'message' => __( 'Are you sure you want to delete this pattern?', 'wporg-patterns' ),
	'redirectUrl' => home_url( '/my-patterns/' ),
];
$encoded_state = wp_json_encode( $init_state );

?>
<div
	<?php echo get_block_wrapper_attributes( [ 'class' => 'is-small is-style-toggle' ] ); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/delete-button"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
>
	<button
		class="wp-block-button__link wp-element-button"
		disabled="disabled"
		data-wp-bind--disabled="!context.postId"
		data-wp-on--click="actions.triggerDelete"
	>
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
			<path fill-rule="evenodd" d="M12 4a3.751 3.751 0 0 0-3.675 3H5v1.5h1.27l.818 8.997a2.75 2.75 0 0 0 2.739 2.501h4.347a2.75 2.75 0 0 0 2.738-2.5L17.73 8.5H19V7h-3.325A3.751 3.751 0 0 0 12 4Zm0 1.5A2.25 2.25 0 0 0 9.878 7h4.244A2.251 2.251 0 0 0 12 5.5Zm4.224 3H7.776l.806 8.861a1.25 1.25 0 0 0 1.245 1.137h4.347a1.25 1.25 0 0 0 1.245-1.137l.805-8.861Z" clip-rule="evenodd"/>
		</svg>
		<?php
		echo wp_kses_post(
			sprintf(
				__( 'Delete <span class="screen-reader-text">"%s"</span>', 'wporg-patterns' ),
				get_the_title( $current_post_id )
			)
		);
		?>
	</button>
</div>
