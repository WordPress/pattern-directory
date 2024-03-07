<?php
/**
 * Title: Single pattern
 * Slug: wporg-pattern-directory-2024/single-my-pattern
 * Inserter: no
 */

$error = isset( $_GET['error'] ) ? $_GET['error'] : false;
$error_notice = '';
if ( 'draft-failed' === $error ) {
	$error_notice = __( 'Your pattern could not be updated, please try again.', 'wporg-patterns' );
}

?>
<!-- wp:group {"layout":{"type":"default"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"align":"wide"} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);">
	<?php if ( $error_notice ) : ?>
	<!-- wp:wporg/notice {"type":"warning","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-wporg-notice is-warning-notice" style="margin-bottom:var(--wp--preset--spacing--30);">
		<div class="wp-block-wporg-notice__icon"></div>
		<div class="wp-block-wporg-notice__content">
			<p><?php echo esc_html( $error_notice ) ?></p>
		</div>
	</div>
	<!-- /wp:wporg/notice -->
	<?php else : ?>
	<!-- wp:wporg/status-notice /-->
	<?php endif; ?>

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"align":"wide"} -->
	<div class="wp-block-group alignwide">
		<!-- wp:post-title {"fontSize":"heading-3"} /-->

		<!-- wp:wporg/copy-button /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:spacer {"height":"var:preset|spacing|50","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
	<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:wporg/pattern-view-control {"align":"full"} -->
		<!-- wp:wporg/pattern-preview /-->
	<!-- /wp:wporg/pattern-view-control -->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"align":"wide"} -->
	<div class="wp-block-group alignwide">
		<!-- wp:post-terms {"term":"wporg-pattern-category"} /-->

		<!-- wp:wporg/favorite-button /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"backgroundColor":"light-grey-2","align":"full"} -->
<div class="wp-block-group has-light-grey-2-background-color has-background alignfull" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);">
	<!-- wp:group {"align":"wide"} -->
	<div class="wp-block-group alignwide">
		<!-- wp:buttons {"layout":{"type":"flex"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-fill is-small"} -->
			<div class="wp-block-button is-small is-style-fill"><a href="[pattern_edit_link]" class="wp-block-button__link wp-element-button">Open in editor</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline is-small"} -->
			<div class="wp-block-button is-style-outline is-small"><a href="[pattern_draft_link]" class="wp-block-button__link wp-element-button">Revert to draft</a></div>
			<!-- /wp:button -->

			<!-- wp:wporg/delete-button /-->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
