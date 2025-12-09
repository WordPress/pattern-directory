<?php
/**
 * Content shown when user is not the current pattern owner.
 */

namespace WordPressdotorg\Pattern_Creator;

?>
<!-- wp:template-part {"slug":"header","className":"has-display-contents"} /-->

<!-- wp:group {"tagName":"main","align":"full","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"left":"var:preset|spacing|edge-space","right":"var:preset|spacing|edge-space","top":"var:preset|spacing|40"}}}} -->
<main class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--edge-space);padding-left:var(--wp--preset--spacing--edge-space)">

	<!-- wp:group {"align":"wide"} -->
	<div class="wp-block-group alignwide">

		<!-- wp:heading {"level":1,"fontSize":"heading-3"} -->
		<h1 class="wp-block-heading has-heading-3-font-size"><?php esc_html_e( 'Create and share patterns for every WordPress site.', 'wporg-patterns' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:wporg/notice {"type":"warning"} -->
		<div class="wp-block-wporg-notice is-warning-notice">
			<div class="wp-block-wporg-notice__icon"></div>
			<div class="wp-block-wporg-notice__content"><p><?php esc_html_e( 'You need to be the pattern\'s author to edit this pattern.', 'wporg-patterns' ); ?></p></div>
		</div>
		<!-- /wp:wporg/notice -->

		<!-- wp:spacer {"height":"var:preset|spacing|50","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->
	</div>
	<!-- /wp:group -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->
