<?php
/**
 * Block Name: Status Notice
 * Description: A notice displaying the current pattern's status.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024\Status_Notice_Block;

use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\get_pattern_unlisted_reason;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register the block.
 */
function init() {
	register_block_type(
		__DIR__ . '/../../../build/blocks/status-notice',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}

/**
 * Render the block content.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	$post_id = $block->context['postId'];
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		return '';
	}

	$type = 'info';
	$status = get_post_status( $post_id );

	$message = '';
	switch ( $status ) {
		case 'pending-review': // Potential spam.
		case 'pending':
			$type = 'alert';
			$message .= '<p>';
			$message .= '<strong>' . __( 'Review pending.', 'wporg-patterns' ) . '</strong> ';
			$message .= __( 'This pattern is only visible to you. Once approved it will be published to everyone.', 'wporg-patterns' );
			$message .= '</p>';
			$message .= '<p>' . __( 'All patterns submitted to WordPress.org are subject to both automated and manual approval. It might take a few days for your pattern to be approved.', 'wporg-patterns' ) . '</p>';
			$message .= '<p>' . __( 'Reviewers look for content that may be problematic (copyright or trademark issues) and whether your pattern works as intended.', 'wporg-patterns' ) . '</p>';
			break;
		case 'draft':
			$message .= '<p>';
			$message .= '<strong>' . __( 'Saved as draft.', 'wporg-patterns' ) . '</strong> ';
			$message .= __( 'This pattern is only visible to you. When you’re ready, submit it to be published to everyone.', 'wporg-patterns' );
			$message .= '</p>';
			$message .= '<p>' . __( 'Patterns can be saved as a draft which can be submitted for approval at any time. This allows you to save your design and come back to it later to submit.', 'wporg-patterns' ) . '</p>';
			break;
		case 'unlisted':
			$type = 'warning';
			$reason = get_pattern_unlisted_reason( $post_id );
			$message .= '<p>';
			$message .= '<strong>' . __( 'Pattern declined.', 'wporg-patterns' ) . '</strong> ';
			$message .= __( 'WordPress.org has chosen not to include this pattern in the directory.', 'wporg-patterns' );
			$message .= '</p>';
			if ( $reason ) {
				$message .= sprintf(
					'<p>%s %s</p>',
					__( 'WordPress.org has removed your pattern from the directory for the following reason:', 'wporg-patterns' ),
					$reason
				);
			}
			$message .= '<p>' . __( 'You can update your pattern to resubmit it for approval at any time.', 'wporg-patterns' ) . '</p>';
			break;
		case 'publish':
			$type = 'tip';
			$message .= '<p>';
			$message .= '<strong>' . __( 'Pattern published!', 'wporg-patterns' ) . '</strong> ';
			$message .= __( 'Your new design is now available to everyone.', 'wporg-patterns' );
			$message .= '</p>';
			break;
	}

	$markup = '<!-- wp:wporg/notice {"type":"%1$s"} -->
		<div class="wp-block-wporg-notice is-%1$s-notice">
			<div class="wp-block-wporg-notice__icon"></div>
			<div class="wp-block-wporg-notice__content">%2$s</div>
		</div>
		<!-- /wp:wporg/notice -->';

	return do_blocks( sprintf( $markup, $type, $message ) );
}
