<?php
/**
 * Inject the <style> tags into the content itself so that it's available in the API.
 *
 * By default these styles are injected by a hook (ex, wp_footer) that is not triggered
 * in an API request, so the content we retrieve from the API is missing it.
 *
 * We need to add styles for both the layout & element styles (link color), and unhook
 * the existing core & Gutenberg hooks.
 *
 * See https://github.com/WordPress/gutenberg/issues/38167 & https://github.com/WordPress/gutenberg/issues/35376.
 */

namespace WordPressdotorg\Pattern_Directory\Theme\Inline_Styles;
use WP_Block_Type_Registry;

/**
 * Renders the layout config to the block wrapper.
 *
 * See `wp_render_layout_support_flag`, `gutenberg_render_layout_support_flag`.
 * Copied from https://github.com/WordPress/gutenberg/blob/0ea49d674a376636db5c0722ae41083394df9e60/lib/block-supports/layout.php#L132.
 *
 * @param  string $block_content Rendered block content.
 * @param  array  $block         Block object.
 * @return string                Filtered block content.
 */
function render_layout_support_styles( $block_content, $block ) {
	$block_type     = WP_Block_Type_Registry::get_instance()->get_registered( $block['blockName'] );
	$support_layout = gutenberg_block_has_support( $block_type, array( '__experimentalLayout' ), false );

	if ( ! $support_layout ) {
		return $block_content;
	}

	$block_gap             = wp_get_global_settings( array( 'spacing', 'blockGap' ) );
	$default_layout        = wp_get_global_settings( array( 'layout' ) );
	$has_block_gap_support = isset( $block_gap ) ? null !== $block_gap : false;
	$default_block_layout  = _wp_array_get( $block_type->supports, array( '__experimentalLayout', 'default' ), array() );
	$used_layout           = isset( $block['attrs']['layout'] ) ? $block['attrs']['layout'] : $default_block_layout;
	if ( isset( $used_layout['inherit'] ) && $used_layout['inherit'] ) {
		if ( ! $default_layout ) {
			return $block_content;
		}
		$used_layout = $default_layout;
	}

	$id        = uniqid();
	$gap_value = _wp_array_get( $block, array( 'attrs', 'style', 'spacing', 'blockGap' ) );
	// Skip if gap value contains unsupported characters.
	// Regex for CSS value borrowed from `safecss_filter_attr`, and used here
	// because we only want to match against the value, not the CSS attribute.
	$gap_value = preg_match( '%[\\\(&=}]|/\*%', $gap_value ) ? null : $gap_value;
	$style     = gutenberg_get_layout_style( ".wp-container-$id", $used_layout, $has_block_gap_support, $gap_value );
	// This assumes the hook only applies to blocks with a single wrapper.
	// I think this is a reasonable limitation for that particular hook.
	$content = preg_replace(
		'/' . preg_quote( 'class="', '/' ) . '/',
		'class="wp-container-' . $id . ' ',
		$block_content,
		1
	);

	return $content . '<style>' . $style . '</style>';
}
remove_filter( 'render_block', 'wp_render_layout_support_flag' );
remove_filter( 'render_block', 'gutenberg_render_layout_support_flag', 10, 2 );
add_filter( 'render_block', __NAMESPACE__ . '\render_layout_support_styles', 10, 2 );

/**
 * Render the elements stylesheet.
 *
 * See `wp_render_elements_support`, `gutenberg_render_elements_support`.
 * Copied from https://github.com/WordPress/gutenberg/blob/0ea49d674a376636db5c0722ae41083394df9e60/lib/block-supports/elements.php#L15.
 *
 * @param  string $block_content Rendered block content.
 * @param  array  $block         Block object.
 * @return string                Filtered block content.
 */
function render_elements_support_styles( $block_content, $block ) {

	if ( ! $block_content ) {
		return $block_content;
	}

	$link_color = null;
	if ( ! empty( $block['attrs'] ) ) {
		$link_color = _wp_array_get( $block['attrs'], array( 'style', 'elements', 'link', 'color', 'text' ), null );
	}

	/*
	* For now we only care about link color.
	* This code in the future when we have a public API
	* should take advantage of WP_Theme_JSON_Gutenberg::compute_style_properties
	* and work for any element and style.
	*/
	if ( null === $link_color ) {
		return $block_content;
	}

	$class_name = 'wp-elements-' . uniqid();

	if ( strpos( $link_color, 'var:preset|color|' ) !== false ) {
		// Get the name from the string and add proper styles.
		$index_to_splice = strrpos( $link_color, '|' ) + 1;
		$link_color_name = substr( $link_color, $index_to_splice );
		$link_color      = "var(--wp--preset--color--$link_color_name)";
	}
	$link_color_declaration = esc_html( safecss_filter_attr( "color: $link_color" ) );

	$style = "<style>.$class_name a{" . $link_color_declaration . ";}</style>\n";

	// Like the layout hook this assumes the hook only applies to blocks with a single wrapper.
	// Retrieve the opening tag of the first HTML element.
	$html_element_matches = array();
	preg_match( '/<[^>]+>/', $block_content, $html_element_matches, PREG_OFFSET_CAPTURE );
	$first_element = $html_element_matches[0][0];
	// If the first HTML element has a class attribute just add the new class
	// as we do on layout and duotone.
	if ( strpos( $first_element, 'class="' ) !== false ) {
		$content = preg_replace(
			'/' . preg_quote( 'class="', '/' ) . '/',
			'class="' . $class_name . ' ',
			$block_content,
			1
		);
	} else {
		// If the first HTML element has no class attribute we should inject the attribute before the attribute at the end.
		$first_element_offset = $html_element_matches[0][1];
		$content              = substr_replace( $block_content, ' class="' . $class_name . '"', $first_element_offset + strlen( $first_element ) - 1, 0 );
	}

	return $content . $style;
}
remove_filter( 'render_block', 'wp_render_elements_support', 10, 2 );
remove_filter( 'render_block', 'gutenberg_render_elements_support', 10, 2 );
add_filter( 'render_block', __NAMESPACE__ . '\render_elements_support_styles', 10, 2 );
