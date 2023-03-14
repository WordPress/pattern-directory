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

?>

	<main id="main" class="site-main col-12" role="main">

		<?php if ( ( is_editing_pattern() && $can_edit ) || ( ! is_editing_pattern() && $is_logged_in ) ) : ?>
			<div id="block-pattern-creator"></div>
		<?php else : ?>
			<div class="entry-content">
				<?php
				// Include block content from other files to simplify HTML markup.
				ob_start();
				if ( ! $is_logged_in ) {
					include __DIR__ . '/log-in.php';
				} elseif ( ! $can_edit ) {
					include __DIR__ . '/not-owner.php';
				}
				echo do_blocks( ob_get_clean() ); // phpcs:ignore
				?>
			</div>
		<?php endif; ?>

	</main><!-- #main -->

<?php
get_footer();
