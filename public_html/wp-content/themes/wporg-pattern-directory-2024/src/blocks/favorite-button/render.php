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
$classes = [ 'is-style-text is-small' ];
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
			<svg class="is-star-filled" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
				<path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path>
			</svg>
			<svg class="is-star-empty" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
				<path fill-rule="evenodd" d="M9.706 8.646a.25.25 0 01-.188.137l-4.626.672a.25.25 0 00-.139.427l3.348 3.262a.25.25 0 01.072.222l-.79 4.607a.25.25 0 00.362.264l4.138-2.176a.25.25 0 01.233 0l4.137 2.175a.25.25 0 00.363-.263l-.79-4.607a.25.25 0 01.072-.222l3.347-3.262a.25.25 0 00-.139-.427l-4.626-.672a.25.25 0 01-.188-.137l-2.069-4.192a.25.25 0 00-.448 0L9.706 8.646zM12 7.39l-.948 1.921a1.75 1.75 0 01-1.317.957l-2.12.308 1.534 1.495c.412.402.6.982.503 1.55l-.362 2.11 1.896-.997a1.75 1.75 0 011.629 0l1.895.997-.362-2.11a1.75 1.75 0 01.504-1.55l1.533-1.495-2.12-.308a1.75 1.75 0 01-1.317-.957L12 7.39z" clip-rule="evenodd"></path>
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
			<svg class="is-star-filled" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
				<path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path>
			</svg>
			<svg class="is-star-empty" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
				<path fill-rule="evenodd" d="M9.706 8.646a.25.25 0 01-.188.137l-4.626.672a.25.25 0 00-.139.427l3.348 3.262a.25.25 0 01.072.222l-.79 4.607a.25.25 0 00.362.264l4.138-2.176a.25.25 0 01.233 0l4.137 2.175a.25.25 0 00.363-.263l-.79-4.607a.25.25 0 01.072-.222l3.347-3.262a.25.25 0 00-.139-.427l-4.626-.672a.25.25 0 01-.188-.137l-2.069-4.192a.25.25 0 00-.448 0L9.706 8.646zM12 7.39l-.948 1.921a1.75 1.75 0 01-1.317.957l-2.12.308 1.534 1.495c.412.402.6.982.503 1.55l-.362 2.11 1.896-.997a1.75 1.75 0 011.629 0l1.895.997-.362-2.11a1.75 1.75 0 01.504-1.55l1.533-1.495-2.12-.308a1.75 1.75 0 01-1.317-.957L12 7.39z" clip-rule="evenodd"></path>
			</svg>
			<span class="wporg-favorite-button__label" data-wp-text="state.labelAction">
				<?php echo $is_favorite ? $remove_label : $add_label; ?>
			</span>
		</button>
	<?php endif; ?>
</div>