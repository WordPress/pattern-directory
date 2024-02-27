<?php

use function WordPressdotorg\Pattern_Directory\Favorite\{get_favorite_count, is_favorite};

$variant = $attributes[ 'variant' ] ?? 'default';

$post_id = $block->context['postId'];
if ( ! $post_id ) {
	return '';
}

$user_id = get_current_user_id();
// Only the small variant should show if anon user.
if ( ! $user_id && 'small' !== $variant ) {
	return '';
}

// Manually enqueue this script, so that it's available for the interactivity view script.
wp_enqueue_script( 'wp-api-fetch' );

$is_favorite = is_favorite( $post_id, $user_id );
$classes = [ 'is-style-text' ];
if ( 'small' === $variant ) {
	$classes[] = 'is-variant-small';
}
$classes = implode( ' ',  $classes );

$tag_name = ! $user_id ? 'span' : 'button';
$favorite_count = get_favorite_count( $post_id );

$add_label = __( 'Add to favorites', 'wporg-patterns' );
$remove_label = __( 'Remove from favorites', 'wporg-patterns' );
$sr_label = sprintf(
	_n(
		'Favorited %s time',
		'Favorited %s times',
		$favorite_count,
		'wporg-patterns'
	),
	$favorite_count
);

// Initial state to pass to Interactivity API.
$init_state = [
	'postId' => $post_id,
	'isFavorite' => $is_favorite,
	'count' => $favorite_count,
	'label' => [
		'add' => $add_label,
		'remove' => $remove_label,
		'screenReader' => __( 'Favorited %s times', 'wporg-patterns' ),
	]
];
$encoded_state = wp_json_encode( $init_state );

?>
<div
	<?php echo get_block_wrapper_attributes( [ 'class' => $classes ] ); // phpcs:ignore ?>
	data-wp-interactive="wporg/patterns/favorite-button"
	data-wp-context="<?php echo esc_attr( $encoded_state ); ?>"
	data-wp-class--is-favorite="context.isFavorite"
>
	<?php if ( 'small' === $variant ) : ?>
		<<?php echo $tag_name; ?>
			class="wporg-favorite-button__button"
			disabled="disabled"
			data-wp-bind--disabled="!context.postId"
			data-wp-on--click="actions.triggerAction"
		>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="pattern-favorite-button__filled" aria-hidden="true" focusable="false">
  				<path d="M11.941 21.175l-1.443-1.32c-5.124-4.67-8.508-7.75-8.508-11.53 0-3.08 2.408-5.5 5.473-5.5 1.732 0 3.394.81 4.478 2.09 1.085-1.28 2.747-2.09 4.478-2.09 3.065 0 5.473 2.42 5.473 5.5 0 3.78-3.383 6.86-8.508 11.54l-1.443 1.31z"></path>
			</svg>
			<span class="wporg-favorite-button__count">
				<span class="screen-reader-text" data-wp-text="state.labelScreenReader">
					<?php echo esc_html( $sr_label ); ?>
				</span>
				<span aria-hidden="true" data-wp-text="state.labelCount">(<?php echo $favorite_count; ?>)</span>
			</span>
			<span class="wporg-favorite-button__label screen-reader-text" data-wp-text="state.labelAction">
				<?php echo $is_favorite ? $remove_label : $add_label; ?>
			</span>
		</<?php echo $tag_name; ?>>
	<?php else : ?>
		<button
			class="wp-block-button__link wp-element-button"
			disabled="disabled"
			data-wp-bind--disabled="!context.postId"
			data-wp-on--click="actions.triggerAction"
		>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="pattern-favorite-button__filled" aria-hidden="true" focusable="false">
				<path d="M11.941 21.175l-1.443-1.32c-5.124-4.67-8.508-7.75-8.508-11.53 0-3.08 2.408-5.5 5.473-5.5 1.732 0 3.394.81 4.478 2.09 1.085-1.28 2.747-2.09 4.478-2.09 3.065 0 5.473 2.42 5.473 5.5 0 3.78-3.383 6.86-8.508 11.54l-1.443 1.31z"></path>
			</svg>
			<span class="wporg-favorite-button__label" data-wp-text="state.labelAction">
				<?php echo $is_favorite ? $remove_label : $add_label; ?>
			</span>
		</button>
	<?php endif; ?>
</div>