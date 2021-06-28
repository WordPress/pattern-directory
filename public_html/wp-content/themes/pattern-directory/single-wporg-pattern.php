<?php
/**
 * The template for displaying all single posts.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPressdotorg\Pattern_Directory\Theme
 */

namespace WordPressdotorg\Pattern_Directory\Theme;
use function WordPressdotorg\Pattern_Directory\Theme\get_all_the_content;

get_header();

$user_has_reported = is_user_logged_in() ? user_has_flagged_pattern() : false;
$raw_block_content = get_the_content();
$parsed_block_content = get_all_the_content( get_the_ID() );

$category_list = wp_get_post_terms( get_the_ID(), 'wporg-pattern-category' );

for ( $i = 0, $c = count( $category_list ); $i < $c; $i++ ) {
	$category_list[ $i ]->link = get_term_link( $category_list[ $i ]->term_id );
}

?>
	<input id="block-data" type="hidden" value="<?php echo rawurlencode( wp_json_encode( $raw_block_content ) ); ?>" />
	<main id="main" class="site-main col-12" role="main">

		<?php
		while ( have_posts() ) :
			the_post();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<div
					hidden
					class="pattern__container"
					data-post-title="<?php the_title(); ?>"
					data-categories="<?php echo rawurlencode( wp_json_encode( $category_list ) ); ?>"
					data-post-id="<?php echo intval( get_the_ID() ); ?>"
					data-logged-in="<?php echo json_encode( is_user_logged_in() ); ?>"
					data-user-has-reported="<?php echo json_encode( $user_has_reported ); ?>"
				>
					<?php echo rawurlencode( wp_json_encode( $parsed_block_content ) ); ?>
				</div>

				<div class="entry-content">
					<h2><?php esc_html_e( 'More from this designer', 'wporg-patterns' ); ?></h2>
					<div class="pattern-grid"></div>
				</div><!-- .entry-content -->
			</article><!-- #post-## -->

		<?php endwhile; ?>

	</main><!-- #main -->

<?php
get_footer();
