<?php

use function WordPressdotorg\Theme\Pattern_Directory_2024\user_has_flagged_pattern;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_REASON;

// Move to footer so that HTML exists on page already.
if ( ! empty( $block->block_type->view_script ) ) {
	wp_enqueue_script( $block->block_type->view_script );
	// Move to footer.
	wp_script_add_data( $block->block_type->view_script, 'group', 1 );
}

$post_id = $block->context['postId'];
if ( ! $post_id ) {
	return;
}

if ( ! current_user_can( 'read' ) ) {
	return;
}

// If this pattern has been reported by this user, it can't be reported again.
if ( user_has_flagged_pattern() ) {
	printf(
		'<div %s>%s</div>',
		get_block_wrapper_attributes(),
		__( 'You&#8217;ve reported this pattern.', 'wporg-patterns' )
	);
	return;
}

$reasons = get_terms( [ 'taxonomy' => FLAG_REASON, 'hide_empty' => false, 'orderby' => 'slug' ] );

?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>>
	<div class="wp-block-button is-small is-style-text">
		<button
			class="wp-block-button__link wp-element-button"
			disabled="disabled"
			data-a11y-dialog-show="report-dialog"
		>
			<?php echo esc_html_e( 'Report this pattern', 'wporg-patterns' ); ?>
		</button>
	</div>
	<div
		id="report-dialog"
		data-a11y-dialog="report-dialog"
		aria-labelledby="report-pattern-dialog-title"
		aria-hidden="true"
		class="wporg-report-pattern__dialog-container"
	>
		<div data-a11y-dialog-hide class="wporg-report-pattern__dialog-overlay"></div>
		<div role="document" class="wporg-report-pattern__dialog-content">
			<div class="wporg-report-pattern__dialog-header">
				<h1 id="report-pattern-dialog-title">
					<?php echo esc_html_e( 'Report this pattern', 'wporg-patterns' ); ?>
				</h1>
				<button type="button" data-a11y-dialog-hide aria-label="<?php echo esc_attr_e( 'Close dialog', 'wporg-patterns' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path></svg></button>
			</div>
			<form method="POST" action="<?php echo esc_url( get_the_permalink( $post_id ) ); ?>">
				<div class="wporg-report-pattern__dialog-body">
					<fieldset class="wporg-report-pattern__dialog-field">
						<legend><?php echo esc_html_e( 'Please choose a reason:', 'wporg-patterns' ); ?></legend>
						<?php foreach ( $reasons as $reason ) : ?>
							<div>
								<input
									type="radio"
									name="report-reason"
									id="report-reason-<?php echo esc_attr( $reason->term_id ); ?>"
									value="<?php echo esc_attr( $reason->term_id ); ?>"
									required
								/>
								<label
									for="report-reason-<?php echo esc_attr( $reason->term_id ); ?>"
								>
									<?php echo esc_attr( $reason->name ); ?>
								</label>
							<div>
						<?php endforeach; ?>
					</fieldset>
					<div class="wporg-report-pattern__dialog-field">
						<label
							for="report-details"
						><?php echo esc_html_e( 'Please provide details (required)', 'wporg-patterns' ); ?></label>
						<textarea
							id="report-details"
							rows="4"
							required
							name="report-details"
						></textarea>
					</div>
					<input type="hidden" name="action" value="report" />
					<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'report-' . $post_id ) ); ?>" />
				</div>
				<div class="wp-block-buttons wporg-report-pattern__dialog-footer">
					<div class="wp-block-button is-small is-style-outline">
						<button type="button" data-a11y-dialog-hide class="wp-block-button__link wp-element-button">
							<?php echo esc_html_e( 'Cancel', 'wporg-patterns' ); ?>
						</button>
					</div>
					<div class="wp-block-button is-small">
						<button type="submit" class="wp-block-button__link wp-element-button">
							<?php echo esc_html_e( 'Report', 'wporg-patterns' ); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
