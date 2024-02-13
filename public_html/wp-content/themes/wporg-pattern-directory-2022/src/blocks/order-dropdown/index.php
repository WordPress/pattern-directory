<?php
/**
 * Block Name: Sort Order Dropdown
 * Description: Display a dropdown filter for sorting the patterns.
 *
 * @package wporg
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2022\Order_Dropdown_Block;

add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type(
		__DIR__ . '/../../../build/blocks/order-dropdown',
		array(
			'render_callback' => __NAMESPACE__ . '\render',
		)
	);
}

/**
 * Helper function to get the sort order options.
 *
 * @return array List of sort orders available.
 */
function get_order_options() {
	return array(
		'date' => __( 'Newest', 'wporg-patterns' ),
		'favorite_count' => __( 'Popular', 'wporg-patterns' ),
	);
}

/**
 * Convert a string "orderby" into the query args for WP_Query.
 *
 * @param string $orderby The current "orderby" value
 * @param array The args which should be used by WP_Query to fetch posts for this orderby.
 */
function get_orderby_args( $orderby ) {
	$args = [];
	if ( 'favorite_count' === $orderby ) {
		$args['orderby'] = 'meta_value_num';
		$args['meta_key'] = 'wporg-pattern-favorites';
	}
	return $args;
}

/**
 * Render the block content.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 *
 * @return string Returns the block markup.
 */
function render( $attributes, $content, $block ) {
	if ( ! empty( $block->block_type->view_script ) ) {
		wp_enqueue_script( $block->block_type->view_script );
		// Move to footer.
		wp_script_add_data( $block->block_type->view_script, 'group', 1 );
	}

	$order_opts = get_order_options();
	$selected = $_GET['orderby'] ?? '';

	$dropdown = '<select name="orderby">';
	foreach ( $order_opts as $value => $label ) {
		$dropdown .= sprintf(
			'<option value="%1$s" %2$s>%3$s</option>',
			$value,
			selected( $value, $selected, false ),
			$label
		);
	}
	$dropdown .= '</select>';

	$wrapper_attributes = get_block_wrapper_attributes();
	return sprintf(
		'<div %1$s>%2$s</div>',
		$wrapper_attributes,
		$dropdown
	);
}
