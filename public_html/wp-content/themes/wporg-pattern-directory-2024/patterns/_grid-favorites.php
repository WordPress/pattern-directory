<?php
/**
 * Title: Pattern Grid (Favorites)
 * Slug: wporg-pattern-directory-2024/grid-favorites
 * Inserter: no
 */

?>
<!-- wp:query {"queryId":0,"query":{"inherit":false,"postType":"wporg-pattern"}} -->
<div class="wp-block-query">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group">
			<!-- wp:search {"showLabel":false,"placeholder":"<?php esc_html_e( 'Search patterns…', 'wporg' ); ?>","width":100,"widthUnit":"%","buttonText":"<?php esc_html_e( 'Search', 'wporg' ); ?>","buttonPosition":"button-inside","buttonUseIcon":true,"className":"is-style-secondary-search-control"} /-->

			<!-- wp:wporg/query-total /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap"},"className":"wporg-query-filters"} -->
		<div class="wp-block-group wporg-query-filters">
			<!-- wp:wporg/query-filter {"key":"category"} /-->
			<!-- wp:wporg/query-filter {"key":"sort","multiple":false} /-->
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
			<!-- wp:wporg/pattern-thumbnail {"isLink":true} /-->

			<!-- wp:post-title {"isLink":true,"fontSize":"small","fontFamily":"inter"} /-->

			<!-- wp:post-author {"avatarSize":24,"fontSize":"small"} /-->

			<!-- wp:wporg/favorite-button {"variant":"small"} /-->
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
		<!-- wp:group {"layout":{"type":"constrained","justifyContent":"left"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Looks like you don&#8217;t have any favorites yet. Tap the heart on any pattern to mark it as a favorite. All your favorite patterns will appear here.', 'wporg-patterns' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Check out these suggestions.', 'wporg-patterns' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- wp:group /-->

		<!-- wp:spacer {"height":"var:preset|spacing|50","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->

		<!-- wp:group {"style":{"spacing":{"padding":{"left":"var:preset|spacing|edge-space","right":"var:preset|spacing|edge-space","top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"backgroundColor":"light-grey-2","layout":{"type":"constrained","wideSize":"1760px"},"align":"full","className":"wporg-patterns-nested-alignfull"} -->
		<div class="wp-block-group alignfull wporg-patterns-nested-alignfull has-light-grey-2-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--edge-space);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--edge-space)">
			<!-- wp:query {"queryId":1,"query":{"inherit":false,"perPage":3,"postType":"wporg-pattern","_id":"empty-favorites"}} -->
			<div class="wp-block-query">
				<!-- wp:query-title {"type":"filter","fontSize":"heading-4","fontFamily":"inter","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} /-->

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
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
