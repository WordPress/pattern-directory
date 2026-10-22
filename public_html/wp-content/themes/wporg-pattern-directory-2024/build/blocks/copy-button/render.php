<?php
/**
 * Render the copy button pattern component.
 *
 * @package WordPress\Pattern_Directory
 */

$variant = $attributes['variant'] ?? 'default';

$current_post_id = $block->context['postId'];
if ( ! $current_post_id ) {
	return '';
}

$label         = 'small' === $variant ? __( 'Copy', 'wporg-patterns' ) : __( 'Copy pattern', 'wporg-patterns' );
$label_success = 'small' === $variant ? __( 'Copied', 'wporg-patterns' ) : __( 'Copied!', 'wporg-patterns' );

$classes = array( 'is-small' );
if ( 'small' === $variant ) {
	$classes[] = 'is-variant-small';
	$classes[] = 'is-style-outline';
}

$current_post = get_post( $current_post_id );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => implode( ' ', $classes ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<button
		class="wp-block-button__link wp-element-button"
		disabled="disabled"
		data-label="<?php echo esc_attr( $label ); ?>"
		data-label-success="<?php echo esc_attr( $label_success ); ?>"
	>
		<?php echo esc_attr( $label ); ?>
	</button>
	<input class="wp-block-wporg-copy-button__content" type="hidden" value="<?php echo rawurlencode( wp_json_encode( $current_post->post_content ) ); ?>" />
</div>
