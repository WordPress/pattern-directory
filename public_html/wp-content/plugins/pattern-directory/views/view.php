<?php
/**
 * Pattern preview template.
 *
 * Forked from core's template-canvas.php.
 *
 * @see https://github.com/WordPress/wordpress-develop/blob/6.1/src/wp-includes/template-canvas.php
 */

namespace WordPressdotorg\Pattern_Directory;
use function WordPressdotorg\Pattern_Creator\MockBlocks\{attach_site_data_filters, remove_site_data_filters};

remove_action( 'wp_footer', 'stats_footer', 101 );

// Attach the filters to override real site data with placeholder content.
attach_site_data_filters();
global $_wp_current_template_content;
// Override the theme template to only output the pattern content.
$_wp_current_template_content = <<<HTML
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
</main>
<!-- /wp:group -->
HTML;

$template_html = get_the_block_template_html();
remove_site_data_filters();

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
			box-sizing: border-box;
			padding-top: 0;
			padding-bottom: 0;
		}
		.wp-site-blocks > * {
			width: 100%;
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

<?php echo $template_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>

<?php wp_footer(); ?>
</body>
</html>
