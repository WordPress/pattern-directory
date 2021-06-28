<?php
/**
 * The main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPressdotorg\Pattern_Directory\Theme
 */

namespace WordPressdotorg\Pattern_Directory\Theme;

get_header();
?>

	<main id="main" class="site-main" role="main">

		<div id="patterns__container" data-is-home="<?php echo is_home(); ?>" data-logged-in="<?php echo json_encode( is_user_logged_in() ); ?>">
			<!-- Filter placeholder -->

			<div class="pattern-grid">
				<?php
				if ( have_posts() ) :
					/* Start the Loop */
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', 'grid' );
					endwhile;
				endif;
				?>
			</div>
		</div>

	</main><!-- #main -->

<?php
get_footer();
