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
			<div class="site-branding row">
				<?php if ( is_home() ) : ?>
					<div class="col col-6 col-offset-2">
						<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html_x( 'WordPress Patterns', 'Site title', 'wporg-patterns' ); ?></a></h1>

						<p class="site-description"><?php esc_html_e( 'Add a beautiful designed, ready to go layout to any WordPress site with a simple copy/paste.', 'wporg-patterns' ); ?></p>
					</div>
				<?php else : ?>
					<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html_x( 'Pattern Directory', 'Site title', 'wporg-patterns' ); ?></a></p>

					<!-- <nav id="site-navigation" class="main-navigation" role="navigation" /> -->
				<?php endif; ?>
				<?php if ( is_home() ) : ?>
				<div class="col col-4 offset-2">
					<div class="highlight-card">
						<h2 class="highlight-card__title"><?php echo esc_html_e( 'Create and share patterns', 'wporg-patterns' ); ?></h2>
						<p><?php esc_html_e( 'Build your own patterns and share them with the WordPress world.', 'wporg-patterns' ); ?></p>
						<a href="#"><?php esc_html_e( 'Learn more', 'wporg-patterns' ); ?></a>
					</div>
				</div>
				<?php endif; ?>
			</div><!-- .site-branding -->
		</header><!-- #masthead -->
