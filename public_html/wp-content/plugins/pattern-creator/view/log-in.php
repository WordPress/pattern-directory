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
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:spacer {"height":"100px"} -->
	<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->
</div>
<!-- /wp:group -->
