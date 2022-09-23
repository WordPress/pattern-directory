<?php
/**
 * Pattern preview template.
 */

namespace WordPressdotorg\Pattern_Directory;

/*
 * Get the template HTML.
 * This needs to run before <head> so that blocks can add scripts and styles in wp_head().
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php wp_head(); ?>
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