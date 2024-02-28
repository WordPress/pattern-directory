<?php
/**
 * Title: Logged out (favorites)
 * Slug: wporg-pattern-directory-2024/logged-out-favorites
 * Inserter: no
 * 
 * This pattern is swapped out automatically when a logged out user visits `/favorites/`.
 */

$login_url = wp_login_url();
$register_url = wp_registration_url();

?>
<!-- wp:group {"layout":{"type":"constrained","contentSize":"30rem","justifyContent":"left"}} -->
<div class="wp-block-group">
	<!-- wp:heading {"level":1,fontSize":"heading-3"} -->
	<h1 class="has-heading-3-font-size"><?php esc_html_e( 'Your favorite patterns', 'wporg-patterns' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Log in to your WordPress.org account and you&#8217;ll be able to see all your favorite patterns in one place.', 'wporg-patterns' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:spacer {"height":"var:preset|spacing|30","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
	<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--30)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Log in', 'wporg-patterns' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

	<!-- wp:paragraph -->
	<p><a href="<?php echo esc_url( $register_url ); ?>"><?php esc_html_e( 'Create an account', 'wporg-patterns' ); ?></a></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->