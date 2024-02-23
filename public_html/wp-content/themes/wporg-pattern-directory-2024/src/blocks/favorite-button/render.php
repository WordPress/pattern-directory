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

$is_favorite = is_favorite( $post_id, $user_id );
$classes = [ 'is-style-text' ];
if ( $is_favorite ) {
	$classes[] = 'is-favorite';
}
if ( 'small' === $variant ) {
	$classes[] = 'is-variant-small';
}
$classes = implode( ' ',  $classes );

$add_label = __( 'Add to favorites', 'wporg-patterns' );
$remove_label = __( 'Remove from favorites', 'wporg-patterns' );

$favorite_count = get_favorite_count( $post_id );

$tag_name = ! $user_id ? 'span' : 'button';

?>
<div
	<?php echo get_block_wrapper_attributes( [ 'class' => $classes ] ); // phpcs:ignore ?>
	data-post-id="<?php echo esc_attr( $post_id ); ?>"
	data-action="<?php echo $is_favorite ? 'remove' : 'add'; ?>"
>
	<?php if ( 'small' === $variant ) : ?>
		<<?php echo $tag_name; ?> class="wporg-favorite-button__button" disabled="disabled">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="pattern-favorite-button__filled" aria-hidden="true" focusable="false">
  				<path d="M11.941 21.175l-1.443-1.32c-5.124-4.67-8.508-7.75-8.508-11.53 0-3.08 2.408-5.5 5.473-5.5 1.732 0 3.394.81 4.478 2.09 1.085-1.28 2.747-2.09 4.478-2.09 3.065 0 5.473 2.42 5.473 5.5 0 3.78-3.383 6.86-8.508 11.54l-1.443 1.31z"></path>
			</svg>
			<span class="wporg-favorite-button__count">
				<span class="screen-reader-text">
				<?php
					printf(
						_n(
							'Favorited %s times',
							'Favorited %s times',
							$favorite_count,
							'wporg-patterns'
						),
						$favorite_count
					);
				?>
				</span>
				<span aria-hidden="true">(<?php echo $favorite_count; ?>)</span>
			</span>
			<span class="wporg-favorite-button__label screen-reader-text">
				<?php echo $is_favorite ? $remove_label : $add_label; ?>
			</span>
		</<?php echo $tag_name; ?>>
	<?php else : ?>
		<button class="wp-block-button__link wp-element-button" disabled="disabled">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="pattern-favorite-button__filled" aria-hidden="true" focusable="false">
				<path d="M11.941 21.175l-1.443-1.32c-5.124-4.67-8.508-7.75-8.508-11.53 0-3.08 2.408-5.5 5.473-5.5 1.732 0 3.394.81 4.478 2.09 1.085-1.28 2.747-2.09 4.478-2.09 3.065 0 5.473 2.42 5.473 5.5 0 3.78-3.383 6.86-8.508 11.54l-1.443 1.31z"></path>
			</svg>
			<span class="wporg-favorite-button__label">
				<?php echo $is_favorite ? $remove_label : $add_label; ?>
			</span>
		</button>
	<?php endif; ?>
</div>