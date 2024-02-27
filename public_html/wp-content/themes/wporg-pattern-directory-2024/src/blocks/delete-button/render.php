<?php

$post_id = $block->context['postId'];
if ( ! $post_id ) {
	return;
}

// Check if the user has permissions.
if ( ! current_user_can( 'edit_post', $post_id ) ) {
	return;
}

// Manually enqueue this script, so that it's available for the interactivity view script.
wp_enqueue_script( 'wp-api-fetch' );

// Initial state to pass to Interactivity API.
$init_state = [
	'postId' => $post_id,
	'message' => __( 'Are you sure you want to delete this pattern?', 'wporg-patterns' ),
	'redirectUrl' => home_url( '/my-patterns/' ),
];
$encoded_state = wp_json_encode( $init_state );

?>
<div
	<?php echo get_block_wrapper_attributes( [ 'class' => 'is-small is-style-outline' ] ); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/delete-button"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
>
	<button
		class="wp-block-button__link wp-element-button"
		disabled="disabled"
		data-wp-bind--disabled="!context.postId"
		data-wp-on--click="actions.triggerDelete"
	>
		<?php echo esc_attr_e( 'Delete pattern', 'wporg-patterns' ); ?>
	</button>
</div>
