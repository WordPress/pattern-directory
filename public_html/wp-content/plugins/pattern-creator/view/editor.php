<?php
/**
 * Pattern Creator template.
 */

namespace WordPressdotorg\Pattern_Creator;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

get_header();

// @todo Permissions TBD, see https://github.com/WordPress/pattern-directory/issues/30.

$post_type_obj = get_post_type_object( POST_TYPE );

if ( is_singular( POST_TYPE ) ) {
	$page_title   = __( 'Update a Block Pattern', 'wporg-patterns' );
	if ( ! is_user_logged_in() ) {
		$page_content = __( 'You need to be logged in to edit this block pattern.', 'wporg-patterns' );
	} else {
		$page_content = __( "You need to be the pattern's author to edit this pattern.", 'wporg-patterns' );
	}
} else {
	$page_title   = __( 'Submit a Block Pattern', 'wporg-patterns' );
	$page_content = __( 'You need to be logged in to create a block pattern.', 'wporg-patterns' );
}
?>

	<main id="main" class="site-main col-12" role="main">

		<?php if ( ( is_singular( POST_TYPE ) && current_user_can( 'edit_post', get_the_ID() ) ) || current_user_can( $post_type_obj->cap->create_posts ) ) : ?>
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
