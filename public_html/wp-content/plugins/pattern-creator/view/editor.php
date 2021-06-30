<?php
/**
 * Pattern Creator template.
 */

namespace WordPressdotorg\Pattern_Creator;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

get_header();

// @todo Permissions TBD, see https://github.com/WordPress/pattern-directory/issues/30.

$is_logged_in = is_user_logged_in();
$can_edit     = current_user_can( 'edit_post', get_the_ID() );

if ( is_singular( POST_TYPE ) ) {
	$page_title   = __( 'Update a Block Pattern', 'wporg-patterns' );
	$page_content = '';
	if ( ! $is_logged_in ) {
		$page_content = __( 'You need to be logged in to edit this block pattern.', 'wporg-patterns' );
	} elseif ( ! $can_edit ) {
		$page_content = __( "You need to be the pattern's author to edit this pattern.", 'wporg-patterns' );
	}
} else {
	$page_title   = __( 'Submit a Block Pattern', 'wporg-patterns' );
	$page_content = '';
	if ( ! $is_logged_in ) {
		$page_content = __( 'You need to be logged in to create a block pattern.', 'wporg-patterns' );
	}
}
?>

	<main id="main" class="site-main col-12" role="main">

		<?php if ( ( is_singular( POST_TYPE ) && $can_edit ) || ( ! is_singular( POST_TYPE ) && $is_logged_in ) ) : ?>
			<div id="block-pattern-creator"></div>
		<?php else : ?>
			<section class="no-results not-found">
				<header class="page-header">
					<h1 class="page-title"><?php echo esc_html( $page_title ); ?></h1>
				</header><!-- .page-header -->

				<div class="page-content">
					<p>
						<?php echo esc_html( $page_content ); ?>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" rel="nofollow"><?php esc_html_e( 'Log in to WordPress.org.', 'wporg-patterns' ); ?></a>
					</p>
				</div>
			</section>
		<?php endif; ?>

	</main><!-- #main -->

<?php
get_footer();
