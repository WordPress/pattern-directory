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
		.wp-block-media-text__media svg,
		.wp-block-site-logo svg {
			vertical-align: middle;
			width: 100%;
			max-height: 200px;
		}
		.wp-block-site-logo svg {
			max-height: 120px;
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
<?php
	// Attach the filters to override real site data with placeholder content.
	attach_site_data_filters();
	// phpcs:ignore -- Allow output from do_blocks.
	echo do_blocks( '<!-- wp:post-content {"layout":{"inherit":true}} /-->' );
	remove_site_data_filters();
?>
</div>

<?php wp_footer(); ?>
</body>
</html>
