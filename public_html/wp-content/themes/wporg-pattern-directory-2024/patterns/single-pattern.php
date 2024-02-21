<?php
/**
 * Title: Single pattern
 * Slug: wporg-pattern-directory-2024/single-pattern
 * Inserter: no
 */

?>
<!-- wp:group {"layout":{"type":"default"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"align":"wide"} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);">
		<!-- wp:post-title {"style":{"typography":{"fontWeight":"700"}},"fontSize":"heading-3","fontFamily":"inter"} /-->

		<!-- wp:post-terms {"term":"wporg-pattern-category"} /-->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:wporg/copy-button /-->

			<!-- wp:wporg/favorite-button /-->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"backgroundColor":"light-grey-2","align":"full"} -->
	<div class="wp-block-group has-light-grey-2-background-color has-background alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);">
		<!-- wp:wporg/pattern-view-control {"align":"full"} -->
			<!-- wp:wporg/pattern-preview /-->
		<!-- /wp:wporg/pattern-view-control -->

		<!-- wp:group {"layout":{"type":"default"},"align":"wide"} -->
		<div class="wp-block-group alignwide">
			<!-- wp:paragraph {"align":"right","fontSize":"small"} -->
			<p class="has-text-align-right has-small-font-size">Report this pattern</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"layout":{"type":"default"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"align":"wide"} -->
	<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);">
		<!-- wp:heading -->
		<h2><?php esc_html_e( 'More from this designer', 'wporg-patterns' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:avatar {"size":48} /-->

			<!-- wp:post-author-name {"isLink":true,"style":{"typography":{"fontStyle":"normal","fontWeight":"700"},"elements":{"link":{"color":{"text":"var:preset|color|charcoal-1"}}}},"textColor":"charcoal-1"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:query {"queryId":1,"query":{"inherit":false,"perPage":4,"postType":"wporg-pattern","override":"more-by-author"}} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"grid","columnCount":4}} -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"0"}}} -->
				<div class="wp-block-group">
					<!-- wp:wporg/pattern-thumbnail {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} /-->

					<!-- wp:post-title {"isLink":true,"fontSize":"small","fontFamily":"inter","style":{"typography":{"fontWeight":"700"}}} /-->

					<!-- wp:wporg/favorite-button {"variant":"small"} /-->
				</div>
				<!-- /wp:group -->
			<!-- /wp:post-template -->
		</div>
		<!-- /wp:query -->
	</div>
	<!-- /wp:group -->