<?php
/**
 * Title: Single pattern
 * Slug: wporg-pattern-directory-2024/single-pattern
 * Inserter: no
 */

$status = isset( $_GET['status'] ) ? $_GET['status'] : false;
$notice = '';
$notice_type = 'warning';

if ( 'report-failed' === $status ) {
	$notice = __( 'Your pattern report could not be saved. Please try again.', 'wporg-patterns' );
} else if ( 'logged-out' === $status ) {
	$notice = __( 'You must be logged in to report a pattern.', 'wporg-patterns' );
} else if ( 'reported' === $status ) {
	$notice_type = 'info';
	$notice = __( 'Your report has been submitted.', 'wporg-patterns' );
}

?>
<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|50"}}},"align":"full"} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);">
	<?php if ( $notice ) : ?>
	<!-- wp:wporg/notice {"align":"wide","type":"<?php echo $notice_type; ?>","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-wporg-notice alignwide is-<?php echo $notice_type; ?>-notice" style="margin-bottom:var(--wp--preset--spacing--30);">
		<div class="wp-block-wporg-notice__icon"></div>
		<div class="wp-block-wporg-notice__content">
			<p><?php echo esc_html( $notice ) ?></p>
		</div>
	</div>
	<!-- /wp:wporg/notice -->
	<?php endif; ?>

	<!-- wp:post-title {"align":"wide","fontSize":"heading-3"} /-->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"align":"wide"} -->
	<div class="wp-block-group alignwide">
		<!-- wp:wporg/copy-button /-->
		<!-- wp:wporg/favorite-button /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
	<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:wporg/pattern-view-control {"align":"full","style":{"spacing":{"margin":{"top":"0"}}}} -->
		<!-- wp:wporg/pattern-preview /-->
	<!-- /wp:wporg/pattern-view-control -->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}},"align":"wide"} -->
	<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--30)">
		<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|10"}}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Tags:', 'wporg-patterns' ); ?></p>
			<!-- /wp:paragraph -->
			
			<!-- wp:post-terms {"term":"wporg-pattern-category","fontSize":"normal"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:wporg/report-pattern /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|50"},"margin":{"top":"0"}}},"layout":{"type":"constrained"},"align":"full"} -->
<div class="wp-block-group alignfull" style="margin-top:0;padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:group {"align":"wide"} -->
	<div class="wp-block-group alignwide">
		<!-- wp:query {"queryId":1,"query":{"inherit":false,"perPage":3,"postType":"wporg-pattern","_id":"more-by-author"}} -->
		<div class="wp-block-query">
			<!-- wp:wporg/query-has-results -->
				<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
				<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--40)">
					<!-- wp:heading {"style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"fontSize":"large","fontFamily":"inter"} -->
					<h2 class="wp-block-heading has-inter-font-family has-large-font-size" style="font-style:normal;font-weight:600"><?php esc_html_e( 'More from this designer', 'wporg-patterns' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
					<div class="wp-block-group">
						<!-- wp:avatar {"size":24,"style":{"border":{"radius":"100%"}}} /-->

						<!-- wp:post-author-name {"isLink":true,"style":{"typography":{"fontStyle":"normal"},"elements":{"link":{"color":{"text":"var:preset|color|charcoal-1"}}}},"textColor":"charcoal-1","fontSize":"small"} /-->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			<!-- /wp:wporg/query-has-results -->
			
			<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"grid","columnCount":3}} -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"5px"}}} -->
				<div class="wp-block-group">
					<!-- wp:wporg/pattern-thumbnail {"isLink":true} /-->

					<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
					<div class="wp-block-group">
						<!-- wp:post-title {"isLink":true,"fontSize":"small","fontFamily":"inter"} /-->

						<!-- wp:wporg/favorite-button {"variant":"small"} /-->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
