<?php
// phpcs:disable WordPress.Files.FileName -- Allow underscore for pattern partial.
/**
 * Title: Pattern Grid (Mine)
 * Slug: wporg-pattern-directory-2024/grid-mine
 * Inserter: no
 */

?>
<!-- wp:query {"align":"wide","queryId":0,"query":{"inherit":false,"postType":"wporg-pattern"},"className":"wporg-my-patterns","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide wporg-my-patterns">
	<!-- wp:navigation {"menuSlug":"statuses","ariaLabel":"<?php esc_attr_e( 'Status menu', 'wporg-patterns' ); ?>","overlayMenu":"never","layout":{"type":"flex","orientation":"horizontal","justifyContent":"left","flexWrap":"nowrap"},"fontSize":"small","className":"is-style-button-list"} /-->

	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group">
			<!-- wp:wporg/query-total /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap"},"className":"wporg-query-filters"} -->
		<div class="wp-block-group wporg-query-filters">
			<!-- wp:wporg/query-filter {"key":"status"} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:spacer {"height":"var:preset|spacing|50","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
	<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"grid","columnCount":3}} -->
		<!-- wp:group {"style":{"spacing":{"blockGap":"5px"}}} -->
		<div class="wp-block-group">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"light-grey-2","layout":{"type":"constrained"},"className":"wporg-pattern-thumbnail__container"} -->
			<div class="wp-block-group has-light-grey-2-background-color has-background wporg-pattern-thumbnail__container" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
				<!-- wp:wporg/pattern-preview {"isLink":true} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:wporg/post-status /-->

			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
			<div class="wp-block-group">
				<!-- wp:post-title {"isLink":true,"fontSize":"small","fontFamily":"inter"} /-->

				<!-- wp:button {"className":"is-style-text is-small is-edit-link","metadata":{"bindings":{"text":{"source":"wporg-pattern/edit-label"},"url":{"source":"wporg-pattern/edit-url"}}}} -->
				<div class="wp-block-button is-style-text is-small is-edit-link"><a href="#" class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Edit', 'wporg-patterns' ); ?></a></div>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->

	<!-- wp:query-pagination -->
		<!-- wp:query-pagination-previous /-->

		<!-- wp:query-pagination-numbers /-->

		<!-- wp:query-pagination-next /-->
	<!-- /wp:query-pagination -->

	<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
	<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:query-no-results -->
		<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0"}}}} -->
		<p style="margin-top:0"><?php esc_html_e( 'Anyone can create and share patterns using the familiar block editor. Design helpful starting points for yourself and any WordPress site.', 'wporg-patterns' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/new-pattern/' ) ); ?>"><?php esc_html_e( 'Create your first pattern', 'wporg-patterns' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:spacer {"height":"var:preset|spacing|40","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--40)" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
