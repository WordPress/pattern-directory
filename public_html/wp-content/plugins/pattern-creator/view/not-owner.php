<?php
/**
 * Content shown when user is not the current pattern owner.
 */

namespace WordPressdotorg\Pattern_Creator;

?>
<!-- wp:group {"layout":{"type":"constrained","wideSize":"940px"}} -->
<div class="wp-block-group">
	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"33.33%"} -->
		<div class="wp-block-column" style="flex-basis:33.33%">
			<!-- wp:image {"sizeSlug":"large"} -->
			<figure class="wp-block-image size-large"><img src="<?php echo esc_url( get_theme_file_uri( 'images/masthead-bg.png' ) ); ?>" alt=""/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"66.66%"} -->
		<div class="wp-block-column" style="flex-basis:66.66%">
			<!-- wp:heading {"level":1,"fontSize":"large"} -->
			<h1 class="wp-block-heading has-large-font-size"><?php esc_html_e( 'Create and share patterns for every WordPress site.', 'wporg-patterns' ); ?></h1>
			<!-- /wp:heading -->

			<!-- wp:wporg/notice {"type":"warning"} -->
			<div class="wp-block-wporg-notice is-warning-notice">
				<div class="wp-block-wporg-notice__icon"></div>
				<div class="wp-block-wporg-notice__content"><p><?php esc_html_e( 'You need to be the pattern\'s author to edit this pattern.', 'wporg-patterns' ); ?></p></div>
			</div>
			<!-- /wp:wporg/notice -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:spacer {"height":"100px"} -->
	<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
