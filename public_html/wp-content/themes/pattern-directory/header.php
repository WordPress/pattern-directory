<?php
/**
 * The Header template for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPressdotorg\Pattern_Directory\Theme
 */

namespace WordPressdotorg\Pattern_Directory\Theme;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

$GLOBALS['pagetitle'] = wp_get_document_title();
global $wporg_global_header_options;
if ( ! isset( $wporg_global_header_options['in_wrapper'] ) ) {
	$wporg_global_header_options['in_wrapper'] = '';
}
$wporg_global_header_options['in_wrapper'] .= '<a class="skip-link screen-reader-text" href="#content">' . esc_html__( 'Skip to content', 'wporg-patterns' ) . '</a>';

get_template_part( 'header', 'wporg' );
?>
<div id="page" class="site">
	<div id="content" class="site-content">
		<header id="masthead" class="site-header <?php echo is_home() ? 'home' : ''; ?>" role="banner">
			<div class="site-branding">
				<?php if ( is_home() ) : ?>
					<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html_x( 'Pattern Directory', 'Site title', 'wporg-patterns' ); ?></a></h1>

					<p class="site-description"><?php esc_html_e( 'Add a beautifully designed, ready to go layout to any WordPress site with a simple copy/paste.', 'wporg-patterns' ); ?></p>
					<?php get_search_form(); ?>
				<?php else : ?>
					<div>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
							<?php esc_html_e( 'All Patterns', 'wporg-patterns' ); ?>
						</a>
						<span class="sep">/</span>
						<span class="is-current-page">
							<?php
							if ( is_singular( POST_TYPE ) ) {
								esc_html_e( 'Pattern Details', 'wporg-patterns' );
							} else if ( is_singular() ) {
								the_title();
							} else if ( is_search() ) {
								printf(
									/* translators: Search query. */
									esc_html__( 'Search Results for "%s"', 'wporg-patterns' ),
									get_search_query()
								);
							} else {
								the_archive_title();
							}
							?>
						</span>
					</div>

					<nav id="site-navigation" class="main-navigation" role="navigation">
						<button
							class="menu-toggle dashicons dashicons-arrow-down-alt2"
							aria-controls="primary-menu"
							aria-expanded="false"
							aria-label="<?php esc_attr_e( 'Primary Menu', 'wporg-patterns' ); ?>"
						>
						</button>

						<?php
						wp_nav_menu( array(
							'theme_location' => 'primary',
							'menu_id'        => 'primary-menu',
						) );
						?>
						
						<?php get_search_form(); ?>
					</nav><!-- #site-navigation -->
				<?php endif; ?>
			</div><!-- .site-branding -->
		</header><!-- #masthead -->

		<?php if ( ! is_singular() ) : ?>
		<div class="notice notice-warning notice-alt" style="max-width:960px; margin:1rem auto;">
			<p style="font-size:0.9375rem; margin:0.75rem 0;">
			<?php
				esc_html_e( 'You’re a bit early to the party! This directory hasn’t yet fully launched.', 'wporg-patterns' );
				echo ' <a href="https://github.com/WordPress/pattern-directory/">' . esc_html__( 'Follow along to see our progress.', 'wporg-patterns' ) . '</a>';
			?>
			</p>
		</div>
		<?php endif; ?>
