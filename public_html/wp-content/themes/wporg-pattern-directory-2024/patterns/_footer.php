<?php
/**
 * Title: Footer
 * Slug: wporg-pattern-directory-2024/footer
 * Inserter: no
 */

// @todo none of this content is real, once we have an idea, it should be wrapped in i18n functions.
?>
<!-- wp:columns {"style":{"spacing":{"blockGap":"0px","padding":{"right":"0","left":"0","top":"0","bottom":"0"}},"border":{"bottom":{"color":"var:preset|color|white-opacity-15","style":"solid","width":"1px"}},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"backgroundColor":"charcoal-1","textColor":"white","className":"is-page-footer"} -->
<div class="wp-block-columns has-white-color has-charcoal-1-background-color has-text-color has-background has-link-color is-page-footer" style="border-bottom-color:var(--wp--preset--color--white-opacity-15);border-bottom-style:solid;border-bottom-width:1px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|edge-space","bottom":"var:preset|spacing|50","left":"var:preset|spacing|edge-space"}},"border":{"right":{"color":"var:preset|color|white-opacity-15","width":"1px"},"top":{},"bottom":{},"left":{}}}} -->
	<div class="wp-block-column" style="border-right-color:var(--wp--preset--color--white-opacity-15);border-right-width:1px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--edge-space);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--edge-space)">
		<!-- wp:heading {"fontSize":"heading-4"} -->
		<h2 class="wp-block-heading has-heading-4-font-size">What's a pattern?</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"is-style-short-text"} -->
		<p class="is-style-short-text">Block Patterns are a collection of blocks that you can insert into posts and pages and then customize with your own content. Using a Block Pattern can reduce the time required to create content on your site, as well as being a great way to learn how different blocks can be combined to produce interesting effects. </p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-outline-on-dark"} -->
			<div class="wp-block-button is-style-outline-on-dark"><a class="wp-block-button__link wp-element-button" href="https://wordpress.org/documentation/article/block-pattern/"><?php _e( 'Learn more about using patterns', 'wporg' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|edge-space","bottom":"var:preset|spacing|50","left":"var:preset|spacing|edge-space"}}}} -->
	<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--edge-space);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--edge-space)">
		<!-- wp:heading {"fontSize":"heading-4"} -->
		<h2 class="wp-block-heading has-heading-4-font-size">Create a pattern</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"is-style-short-text"} -->
		<p class="is-style-short-text">It is now possible for designers, site builders, and site owners to share their creations with the world and submit their best designs to the Block Pattern Directory. Themes can reference patterns from the Directory, without loading them with the theme on every site. This helps to share block patterns from one site to the next.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-fill-on-dark"} -->
			<div class="wp-block-button is-style-fill-on-dark"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/new-pattern/' ) ); ?>"><?php _e( 'Create a pattern', 'wporg' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
