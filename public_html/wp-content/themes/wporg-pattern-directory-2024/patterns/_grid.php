<?php
/**
 * Title: Pattern Grid
 * Slug: wporg-pattern-directory-2024/grid
 * Inserter: no
 */

?>
<!-- wp:query {"align":"wide","queryId":0,"query":{"inherit":true},"layout":{"type":"constrained","wideSize":"1760px"}} -->
<div class="wp-block-query alignwide">
	<!-- wp:navigation {"menuSlug":"categories","ariaLabel":"<?php esc_attr_e( 'Category menu', 'wporg-patterns' ); ?>","overlayMenu":"never","layout":{"type":"flex","orientation":"horizontal","justifyContent":"left","flexWrap":"nowrap"},"fontSize":"small","className":"is-style-button-list"} /-->

	<!-- wp:group {"align":"wide","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group">
			<!-- wp:search {"showLabel":false,"placeholder":"<?php esc_html_e( 'Search patterns', 'wporg-patterns' ); ?>","width":100,"widthUnit":"%","buttonText":"<?php esc_html_e( 'Search', 'wporg' ); ?>","buttonPosition":"button-inside","buttonUseIcon":true,"className":"is-style-secondary-search-control"} /-->

			<!-- wp:wporg/query-total /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","flexWrap":"nowrap"},"className":"wporg-query-filters"} -->
		<div class="wp-block-group wporg-query-filters">
			<!-- wp:wporg/query-filter {"key":"curation","multiple":false} /-->
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

			<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
			<div class="wp-block-group">
				<!-- wp:post-title {"isLink":true,"fontSize":"small","fontFamily":"inter"} /-->

				<!-- wp:wporg/favorite-button {"variant":"small"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	<!-- /wp:post-template -->

	<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
		<!-- wp:query-pagination-previous {"label":"<?php esc_attr_e( 'Previous', 'wporg' ); ?>"} /-->

		<!-- wp:query-pagination-numbers /-->

		<!-- wp:query-pagination-next {"label":"<?php esc_attr_e( 'Next', 'wporg' ); ?>"} /-->
	<!-- /wp:query-pagination -->

	<!-- wp:query-no-results -->
		<!-- wp:heading {"textAlign":"center","level":1,"fontSize":"heading-2"} -->
		<h1 class="wp-block-heading has-text-align-center has-heading-2-font-size"><?php esc_attr_e( 'No results found', 'wporg' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center"} -->
		<p class="has-text-align-center">
			<?php printf(
				/* translators: %s is url of the pattern archives. */
				wp_kses_post( __( 'View <a href="%s">all patterns</a> or try a different search. ', 'wporg' ) ),
				esc_url( home_url( '/archives/' ) )
			); ?>
		</p>
		<!-- /wp:paragraph -->
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
