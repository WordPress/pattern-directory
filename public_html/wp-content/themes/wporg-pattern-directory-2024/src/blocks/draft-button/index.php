<?php
/**
 * Block Name: Draft Button
 * Description: A form that reverts the current pattern to a draft.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Draft_Button_Block;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register the block.
 *
 * Server-rendered only, so it is registered here rather than from a built `block.json`: it has no editor
 * side, and the "My pattern" template it sits in is maintained in code.
 */
function init() {
	register_block_type(
		'wporg/draft-button',
		array(
			'uses_context'    => array( 'postId' ),
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}

/**
 * Render the draft button.
 *
 * The status change is a POST, carried by a form the block writes for the post it is rendered against,
 * so it is never a link that a page can trigger by being loaded.
 *
 * @param array     $attributes Block attributes (none).
 * @param string    $content    Block content (none).
 * @param \WP_Block $block      The block instance, for its context.
 * @return string The form, or nothing when the current user cannot draft the pattern.
 */
function render( $attributes, $content, $block ) {
	$post_id = $block->context['postId'] ?? 0;
	if ( ! $post_id || POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="wp-block-button is-style-toggle is-small is-draft-button">
		<form method="post" action="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
			<input type="hidden" name="action" value="draft" />
			<?php wp_nonce_field( 'draft-' . $post_id, '_wpnonce', false ); ?>
			<button type="submit" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Revert to draft', 'wporg-patterns' ); ?></button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
