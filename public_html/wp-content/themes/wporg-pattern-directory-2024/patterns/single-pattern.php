<?php
/**
 * Title: Single pattern
 * Slug: wporg-pattern-directory-2024/single-pattern
 * Inserter: no
 */

?>
<!-- wp:group {"layout":{"type":"default"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"align":"wide"} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);">
	<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"align":"wide"} -->
	<div class="wp-block-group alignwide">
		<!-- wp:post-title {"style":{"typography":{"fontWeight":"700"}},"fontSize":"heading-3","fontFamily":"inter"} /-->

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

	<!-- wp:group {"align":"wide"} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"fontSize":"small"} -->
		<p class="has-small-font-size">Report this pattern</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"backgroundColor":"light-grey-2","align":"full"} -->
<div class="wp-block-group has-light-grey-2-background-color has-background alignfull" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);">
	<!-- wp:group {"align":"wide"} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"fontSize":"heading-4"} -->
		<h2 class="has-heading-4-font-size"><?php esc_html_e( 'More from this designer', 'wporg-patterns' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|10"}}} -->
		<div class="wp-block-group">
			<!-- wp:avatar {"size":24,"style":{"border":{"radius":"100%"}}} /-->

			<!-- wp:post-author-name {"isLink":true,"style":{"typography":{"fontStyle":"normal"},"elements":{"link":{"color":{"text":"var:preset|color|charcoal-1"}}}},"textColor":"charcoal-1"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:query {"queryId":1,"query":{"inherit":false,"perPage":3,"postType":"wporg-pattern","override":"more-by-author"}} -->
		<div class="wp-block-query">
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
