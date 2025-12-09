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

$is_logged_in = is_user_logged_in();
$can_edit     = current_user_can( 'edit_pattern', get_query_var( PATTERN_ID_VAR ) );

$template_html = '';
if ( ( is_editing_pattern() && $can_edit ) || ( ! is_editing_pattern() && $is_logged_in ) ) {
	$template_html = '<div id="block-pattern-creator"></div>';
} else {
	// Include block content from other files to simplify HTML markup.
	ob_start();
	if ( ! $is_logged_in ) {
		include __DIR__ . '/log-in.php';
	} elseif ( ! $can_edit ) {
		include __DIR__ . '/not-owner.php';
	}
	$template_html = sprintf( '<div class="wp-site-blocks">%s</div>', do_blocks( ob_get_clean() ) );
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

	<?php echo $template_html; // phpcs:ignore ?>

	<?php wp_footer(); ?>
</body>
</html>
