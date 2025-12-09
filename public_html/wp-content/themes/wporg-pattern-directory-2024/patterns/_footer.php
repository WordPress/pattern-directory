<?php
// phpcs:disable WordPress.Files.FileName -- Allow underscore for pattern partial.
/**
 * Title: Footer
 * Slug: wporg-pattern-directory-2024/footer
 * Inserter: no
 */

?>
<!-- wp:columns {"style":{"spacing":{"blockGap":"0px","padding":{"right":"0","left":"0","top":"0","bottom":"0"}},"border":{"bottom":{"color":"var:preset|color|white-opacity-15","style":"solid","width":"1px"}},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"backgroundColor":"charcoal-1","textColor":"white","className":"is-page-footer"} -->
<div class="wp-block-columns has-white-color has-charcoal-1-background-color has-text-color has-background has-link-color is-page-footer" style="border-bottom-color:var(--wp--preset--color--white-opacity-15);border-bottom-style:solid;border-bottom-width:1px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|edge-space","bottom":"var:preset|spacing|50","left":"var:preset|spacing|edge-space"}},"border":{"right":{"color":"var:preset|color|white-opacity-15","width":"1px"},"top":{},"bottom":{},"left":{}}}} -->
	<div class="wp-block-column" style="border-right-color:var(--wp--preset--color--white-opacity-15);border-right-width:1px;padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--edge-space);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--edge-space)">
		<!-- wp:heading {"fontSize":"heading-4"} -->
		<h2 class="wp-block-heading has-heading-4-font-size"><?php esc_html_e( 'What&#8217;s a pattern?', 'wporg-patterns' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"is-style-short-text"} -->
		<p class="is-style-short-text"><?php esc_html_e( 'A block pattern is a collection of blocks you can insert into your site and customize with your own content. Patterns save you time when composing pages of any kind and are a great way to learn how blocks can be combined to achieve specific layouts.', 'wporg-patterns' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-outline-on-dark"} -->
			<div class="wp-block-button is-style-outline-on-dark"><a class="wp-block-button__link wp-element-button" href="https://wordpress.org/documentation/article/block-pattern/"><?php echo wp_kses_post( __( 'Learn more<span class="screen-reader-text">about using patterns</span>', 'wporg-patterns' ) ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|edge-space","bottom":"var:preset|spacing|50","left":"var:preset|spacing|edge-space"}}}} -->
	<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--edge-space);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--edge-space)">
		<!-- wp:heading {"fontSize":"heading-4"} -->
		<h2 class="wp-block-heading has-heading-4-font-size"><?php esc_html_e( 'Share your patterns', 'wporg-patterns' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"is-style-short-text"} -->
		<p class="is-style-short-text"><?php esc_html_e( 'Showcase your creations and make them publicly available in the Block Pattern Directory. Submitting a pattern to the directory means it can be referenced in themes and easily reused across sites—without requiring theme authors to bundle pattern code with each theme.', 'wporg-patterns' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-outline-on-dark"} -->
			<div class="wp-block-button is-style-outline-on-dark"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/new-pattern/' ) ); ?>"><?php esc_html_e( 'Create a pattern', 'wporg-patterns' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
