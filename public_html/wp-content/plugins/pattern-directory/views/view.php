<?php
/**
 * Pattern preview template.
 */

namespace WordPressdotorg\Pattern_Directory;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php wp_head(); ?>
	<style type='text/css'>
		/* Vertically center the pattern in the viewport. */
		.wp-site-blocks {
			display:flex;
			align-items:center;
			min-height:100vh;
		}
		.entry-content {
			width:100%;
			pointer-events: none;
		}
	</style>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="wp-site-blocks">
	<div class="entry-content">
		<?php the_content(); ?>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
