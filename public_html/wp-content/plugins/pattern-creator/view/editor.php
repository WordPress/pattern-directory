<?php
/**
 * Pattern Creator template.
 */

namespace WordPressdotorg\Pattern_Creator;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

add_filter( 'body_class', function( $classes ) {
	$classes[] = 'admin-color-modern';
	return $classes;
} );

get_header();

$is_logged_in = is_user_logged_in();
$can_edit     = current_user_can( 'edit_pattern', get_query_var( PATTERN_ID_VAR ) );

$current_page_query_args = array( 'pagename' => 'new-pattern' );
if ( get_query_var( PATTERN_ID_VAR ) ) {
	$current_page_query_args[ PATTERN_ID_VAR ] = get_query_var( PATTERN_ID_VAR );
}
$current_page_url = add_query_arg( $current_page_query_args, home_url() );

?>

	<main id="main" class="site-main col-12" role="main">

		<?php if ( ( is_editing_pattern() && $can_edit ) || ( ! is_editing_pattern() && $is_logged_in ) ) : ?>
			<div id="block-pattern-creator"></div>
		<?php else : ?>
			<div class="entry-content">
				<div class="wp-block-columns alignwide" style="max-width:960px;justify-content:space-between;">
					<div class="wp-block-column" style="flex-basis:32%">
						<figure class="wp-block-image"><img src="<?php echo esc_url( get_theme_file_uri( 'images/masthead-bg.png' ) ); ?>" alt=""></figure>
					</div>
					<div class="wp-block-column" style="flex-basis:64%">
						<h1 style="word-break:normal;hyphens:manual;"><?php esc_html_e( 'Create and share patterns for every WordPress site.', 'wporg-patterns' ); ?></h1>
						<?php if ( ! $is_logged_in ) : ?>
						<p>
							<?php esc_html_e(
								'Anyone can create and share patterns using the familiar block editor. Design helpful starting points for yourself and any WordPress site.',
								'wporg-patterns'
							); ?>
						</p>
						<div style="height:20px" aria-hidden="true" class="wp-block-spacer"></div>
						<div class="wp-block-buttons">
							<div class="wp-block-button">
								<a class="wp-block-button__link has-white-color has-vivid-cyan-blue-background-color has-text-color has-background" href="<?php echo esc_url( wp_login_url( $current_page_url ) ); ?>" rel="nofollow" style="border-radius:0px">
									<?php esc_html_e( 'Log in to WordPress.org to create your pattern.', 'wporg-patterns' ); ?>
								</a>
							</div>
						</div>
						<p>
							<?php
								printf(
									wp_kses_post( __( 'Or <a href="%s">review the guidelines</a>.', 'wporg-patterns' ) ),
									esc_url( home_url( '/about/' ) )
								);
							?>
						</p>
						<?php elseif ( ! $can_edit ) : ?>
							<div class="notice notice-error">
								<p style="font-size:1rem;"><?php esc_html_e( "You need to be the pattern's author to edit this pattern.", 'wporg-patterns' ); ?></p>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>
			</div>
		<?php endif; ?>

	</main><!-- #main -->

<?php
get_footer();
