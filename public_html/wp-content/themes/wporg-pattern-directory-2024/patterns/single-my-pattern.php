<?php
/**
 * Title: Single pattern
 * Slug: wporg-pattern-directory-2024/single-my-pattern
 * Inserter: no
 */

$action_status = isset( $_GET['status'] ) ? $_GET['status'] : false;
$notice = '';
$notice_type = 'warning';
if ( 'draft-failed' === $action_status ) {
	$notice = __( 'Your pattern could not be updated, please try again.', 'wporg-patterns' );
}

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|50","left":"var:preset|spacing|edge-space","right":"var:preset|spacing|edge-space"}}},"layout":{"type":"constrained","contentSize":"1160px","justifyContent":"center","wideSize":"1460px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--edge-space);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--edge-space)">
	<?php if ( $notice ) : ?>
	<!-- wp:wporg/notice {"type":"<?php echo esc_attr( $notice_type ); ?>","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-wporg-notice is-<?php echo esc_attr( $notice_type ); ?>-notice" style="margin-bottom:var(--wp--preset--spacing--30);">
		<div class="wp-block-wporg-notice__icon"></div>
		<div class="wp-block-wporg-notice__content">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	</div>
	<!-- /wp:wporg/notice -->
	<?php else : ?>
	<!-- wp:wporg/status-notice /-->
	<?php endif; ?>

	<!-- wp:post-title {"level":1,"fontSize":"heading-3"} /-->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group">
		<!-- wp:wporg/copy-button /-->
		<!-- wp:wporg/favorite-button /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
	<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:wporg/pattern-view-control {"align":"wide","style":{"spacing":{"margin":{"top":"0"}}}} -->
		<!-- wp:wporg/pattern-preview /-->
	<!-- /wp:wporg/pattern-view-control -->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--30)">
		<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|10"}}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Tags:', 'wporg-patterns' ); ?></p>
			<!-- /wp:paragraph -->
			
			<!-- wp:post-terms {"term":"wporg-pattern-category","fontSize":"normal"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:buttons {"layout":{"type":"flex"},"style":{"spacing":{"blockGap":"var:preset|spacing|10"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-toggle is-small","metadata":{"bindings":{"text":{"source":"wporg-pattern/edit-label"},"url":{"source":"wporg-pattern/edit-url"}}}} -->
			<div class="wp-block-button is-style-toggle is-small"><a href="[pattern_edit_link]" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Edit', 'wporg-patterns' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-toggle is-small is-draft-button"} -->
			<div class="wp-block-button is-style-toggle is-small is-draft-button"><a href="[pattern_draft_link]" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Revert to draft', 'wporg-patterns' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:wporg/delete-button /-->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
