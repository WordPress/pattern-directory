<?php
/**
 * Content shown when user is not logged in.
 */

namespace WordPressdotorg\Pattern_Creator;

$current_page_query_args = array( 'pagename' => 'new-pattern' );
if ( get_query_var( PATTERN_ID_VAR ) ) {
	$current_page_query_args[ PATTERN_ID_VAR ] = get_query_var( PATTERN_ID_VAR );
}
$current_page_url = add_query_arg( $current_page_query_args, home_url() );

?>
<!-- wp:template-part {"slug":"header","className":"has-display-contents"} /-->

<!-- wp:group {"tagName":"main","align":"full","layout":{"type":"constrained","contentSize":"480px","justifyContent":"left"},"style":{"spacing":{"padding":{"left":"var:preset|spacing|edge-space","right":"var:preset|spacing|edge-space","top":"var:preset|spacing|40"}}}} -->
<main class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--edge-space);padding-left:var(--wp--preset--spacing--edge-space)">

	<!-- wp:group -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":1,"fontSize":"heading-3"} -->
		<h1 class="wp-block-heading has-heading-3-font-size"><?php esc_html_e( 'Create and share patterns for every WordPress site.', 'wporg-patterns' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p>
			<?php
			esc_html_e(
				'Anyone can create and share patterns using the familiar block editor. Design helpful starting points for yourself and any WordPress site.',
				'wporg-patterns'
			);
			?>
		</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"vivid-cyan-blue","textColor":"white","style":{"border":{"radius":"0px"}},"className":"is-style-fill"} -->
			<div class="wp-block-button is-style-fill"><a class="wp-block-button__link has-white-color has-vivid-cyan-blue-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url( wp_login_url( $current_page_url ) ); ?>" rel="nofollow" style="border-radius:0px"><?php esc_html_e( 'Log in to WordPress.org to create your pattern.', 'wporg-patterns' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:paragraph -->
		<p>
			<?php
			printf(
				wp_kses_post( __( 'Or <a href="%s">review the guidelines</a>.', 'wporg-patterns' ) ),
				esc_url( home_url( '/about/' ) )
			);
			?>
		</p>
		<!-- /wp:paragraph -->

		<!-- wp:spacer {"height":"var:preset|spacing|50","style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
		<div style="margin-top:0;margin-bottom:0;height:var(--wp--preset--spacing--50)" aria-hidden="true" class="wp-block-spacer"></div>
		<!-- /wp:spacer -->
	</div>
	<!-- /wp:group -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->
