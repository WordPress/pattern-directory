<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Taxonomy_Modifications;

$category_taxonomy = 'wporg-pattern-category';
const CATEGORY_TAXONOMY_ID = 'display-order';

add_action( "{$category_taxonomy}_add_form_fields", __NAMESPACE__ . '\add_ordering_form_field', 0 );
add_action( "{$category_taxonomy}_edit_form_fields", __NAMESPACE__ . '\edit_ordering_form_field', 10, 2 );
add_action( "created_{$category_taxonomy}", __NAMESPACE__ . '\save_term_field' );
add_action( "edited_{$category_taxonomy}", __NAMESPACE__ . '\save_term_field' );
add_filter( "manage_{$category_taxonomy}_custom_column", __NAMESPACE__ . '\add_category_order_column_content', 10, 3 );
add_filter( "manage_edit-{$category_taxonomy}_columns", __NAMESPACE__ . '\add_order_table_heading' );
add_filter( "rest_prepare_{$category_taxonomy}", __NAMESPACE__ . '\rest_prepare_category_response', 10, 2 );

/**
 * Adds a form field for adding an ordinal number to a new category.
 *
 * This is a callback for the `{$taxonomy}_add_form_fields` filter, and it's used to modify the new taxonomy form.
 */
function add_ordering_form_field() {
	echo '
		<div class="form-field">
			<label for="display-order">Display Order</label>
			<input type="number" min="1" name="display-order" id="display-order" />
			<p>This is used to order the categories in the pattern grid navigation.</p>
		</div>
	';
}

/**
 * Adds a form field for editing an ordinal number for a category.
 *
 * This is a callback for the `{$taxonomy}_edit_form_fields` filter, and it's used to modify the edit taxonomy form.
 *
 * @param WP_Term $term Term object.
 */
function edit_ordering_form_field( $term ) {
	$value = get_term_meta( $term->term_id, CATEGORY_TAXONOMY_ID, true );

	echo '
		<tr class="form-field">
			<th>
				<label for="display-order">Display Order</label>
			</th>
			<td>
				<input name="display-order" min="1" id="display-order" type="number" value="' . esc_attr( $value ) . '" />
				<p class="description">This is used to order the categories in the pattern grid navigation.</p>
			</td>
		</tr>
	';

}

/**
 * Saves the custom taxonomy field to the database.
 *
 * This is a callback for the `created_{$taxonomy}` and `edited_{$taxonomy}` filter, and it's used to save meta information to the database.
 *
 * @param integer $term_id Term id.
 */
function save_term_field( $term_id ) {
	/* phpcs:disable WordPress.Security.NonceVerification.Missing */
	update_term_meta(
		$term_id,
		CATEGORY_TAXONOMY_ID,
		absint( wp_unslash( $_POST[ CATEGORY_TAXONOMY_ID ] ) )
	);
}

/**
 * Adds the category ordinal table heading.
 *
 * This is a callback for the `manage_edit-{$taxonomy}_columns` filter, and it's used modify table headings on taxonomy pages.
 *
 * @param string[] $columns Columns that are displayed in the table.
 *
 * @return string[]
 */
function add_order_table_heading( $columns ) {
	// Our our custom column heading
	$columns[ CATEGORY_TAXONOMY_ID ] = __( 'Display Order', 'wporg-patterns' );

	return $columns;
}

/**
 * Adds the custom meta field data to category table row.
 *
 * This is a callback for the `manage_{$taxonomy}_custom_column` filter, and it's used to modify column content.
 *
 * @param string  $content
 * @param string  $column
 * @param integer $term_id
 *
 * @return string
 */
function add_category_order_column_content( $content, $column, $term_id ) {
	$order = get_term_meta( $term_id, CATEGORY_TAXONOMY_ID, true );

	return $order;
}

/**
 * Modifies rest response to include "display_order" for pattern-categories.
 *
 * This is a callback for the `rest_prepare_{$taxonomy}` filter, and it's used to modify rest responses.
 *
 * @param WP_REST_Response $response
 * @param WP_Term          $term
 *
 * @return WP_REST_Response
 */
function rest_prepare_category_response( $response, $term ) {
	$order_num = get_term_meta( $term->term_id, CATEGORY_TAXONOMY_ID, true );

	// If it's empty we'll make it very low priority
	if ( empty( $order_num ) ) {
		$order_num = 9999;
	}

	$response->data['display_order'] = (int) $order_num;

	return $response;
}

