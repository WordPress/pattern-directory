<?php
/**
 * Pattern preview template.
 */

namespace WordPressdotorg\Pattern_Directory;
use function WordPressdotorg\Pattern_Creator\MockBlocks\{attach_site_data_filters, remove_site_data_filters};

remove_action( 'wp_footer', 'stats_footer', 101 );

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
			margin-top: 0;
			margin-bottom: 0;
		}
		.wp-block-image svg,
		.wp-block-video svg,
		.wp-block-media-text__media svg {
			vertical-align: middle;
			width: 100%;
			max-height: 200px;
		}
		/*
		 * Workaround for placeholder color when used in a white-background cover block.
		 * TT1 sets the color in covers to white, which makes the placeholder invisible.
		 */
		.wp-block-cover__background.has-white-background-color + .wp-block-cover__inner-container {
			color: var(--global--color-primary);
		}
	</style>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="wp-site-blocks">
	<div class="entry-content">
	<?php
		// Attach the filters to override real site data with placeholder content.
		attach_site_data_filters();
		the_content();
		remove_site_data_filters();
	?>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
