<?php
/**
 * The template for displaying all single posts.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPressdotorg\Theme
 */

namespace WordPressdotorg\Theme;

get_header();
?>

	<main id="main" class="site-main col-12" role="main">

		<?php
		while ( have_posts() ) :
			the_post();
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
					<p>A large hero section with an example background image and a heading in the center.</p>
					<div class="pattern-actions">
						<button class="button button-primary">Copy Pattern</button>
						<button class="button">Add to favorites</button>
					</div>
				</header><!-- .entry-header -->

				<div id="wporg-pattern-container" hidden>
					<?php echo rawurlencode( wp_json_encode( get_the_content() ) ); ?>
				</div>

				<div class="entry-content">
					<h2>More from this designer</h2>
					<div class="pattern-grid">
						<ul>
							<li>Pattern A</li>
							<li>Pattern B</li>
							<li>Pattern C</li>
						</ul>
					</div>
				</div><!-- .entry-content -->
			</article><!-- #post-## -->

		<?php endwhile; ?>

	</main><!-- #main -->

<?php
get_footer();
