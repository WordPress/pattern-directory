<?php
/**
 * List a translated pattern once when browsing a locale, in place of its English original.
 *
 * @package WordPressdotorg\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Translated_Originals;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Labels an English original with each locale it has a published translation into.
 */
const TAXONOMY = 'wporg-pattern-translated';

/**
 * Register the taxonomy.
 */
function register_translated_taxonomy(): void {
	register_taxonomy( TAXONOMY, POST_TYPE, array( 'public' => false ) );
}
add_action( 'init', __NAMESPACE__ . '\register_translated_taxonomy' );

/**
 * Leave out English originals that have a published translation into the requested locale.
 *
 * Searches and the Popular sort keep both, as translations rarely match English queries or have favorites.
 *
 * @param string[]  $clauses The query's SQL clauses.
 * @param \WP_Query $query   The query.
 * @return string[] The clauses.
 */
function filter_clauses( array $clauses, \WP_Query $query ): array {
	global $wpdb;

	if ( ! $query->get( 'hide_translated_originals' ) || $query->is_search() || 'wporg-pattern-favorites' === $query->get( 'meta_key' ) ) {
		return $clauses;
	}

	$locale = $query->meta_query->queries['orderby_locale']['value'][0] ?? '';
	$term   = is_string( $locale ) && '' !== $locale ? get_term_by( 'name', $locale, TAXONOMY ) : false;
	if ( ! $term ) {
		return $clauses;
	}

	// Not a `tax_query`, which `tax_relation=OR` would turn into "or not translated".
	$clauses['where'] .= $wpdb->prepare(
		" AND {$wpdb->posts}.ID NOT IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d )",
		$term->term_taxonomy_id
	);

	return $clauses;
}
add_filter( 'posts_clauses', __NAMESPACE__ . '\filter_clauses', PHP_INT_MAX, 2 );

/**
 * Label a translation's original when the translation is published, and unlabel it when it's unpublished.
 *
 * @param string   $new_status New post status.
 * @param string   $old_status Old post status.
 * @param \WP_Post $post       Post whose status changed.
 */
function update_on_status_change( string $new_status, string $old_status, \WP_Post $post ): void {
	if ( $new_status === $old_status || ! in_array( 'publish', array( $new_status, $old_status ), true ) ) {
		return;
	}

	$locale = get_translation_locale( $post );
	if ( ! $locale ) {
		return;
	}

	if ( 'publish' === $new_status ) {
		wp_add_object_terms( (int) $post->post_parent, $locale, TAXONOMY );
	} else {
		unlabel_unless_translated( (int) $post->post_parent, $locale );
	}
}
add_action( 'transition_post_status', __NAMESPACE__ . '\update_on_status_change', 10, 3 );

/**
 * Unlabel a published translation's original once the translation is deleted.
 *
 * The locale is read before deletion, as core removes the meta first.
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post    Post being deleted.
 */
function update_on_delete( int $post_id, \WP_Post $post ): void {
	static $locales = array();

	if ( 'before_delete_post' === current_action() ) {
		if ( 'publish' === $post->post_status ) {
			$locales[ $post_id ] = get_translation_locale( $post );
		}
		return;
	}

	if ( ! empty( $locales[ $post_id ] ) ) {
		unlabel_unless_translated( (int) $post->post_parent, $locales[ $post_id ] );
	}
	unset( $locales[ $post_id ] );
}
add_action( 'before_delete_post', __NAMESPACE__ . '\update_on_delete', 10, 2 );
add_action( 'deleted_post', __NAMESPACE__ . '\update_on_delete', 10, 2 );

/**
 * Remove an original's label for a locale, unless another published translation into it remains.
 *
 * @param int    $original_id The original's post ID.
 * @param string $locale      The locale.
 */
function unlabel_unless_translated( int $original_id, string $locale ): void {
	$remaining = get_posts(
		array(
			'post_type'      => POST_TYPE,
			'post_parent'    => $original_id,
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_key'       => 'wpop_locale',
			'meta_value'     => $locale,
		)
	);

	if ( ! $remaining ) {
		wp_remove_object_terms( $original_id, $locale, TAXONOMY );
	}
}

/**
 * The locale of a translated pattern.
 *
 * Only the translation pipeline gives a pattern a parent.
 *
 * @param \WP_Post $post The pattern.
 * @return string The locale, or an empty string if the pattern isn't a translation.
 */
function get_translation_locale( \WP_Post $post ): string {
	if ( POST_TYPE !== $post->post_type || ! $post->post_parent ) {
		return '';
	}

	$locale = (string) get_post_meta( $post->ID, 'wpop_locale', true );

	return 'en_US' === $locale ? '' : $locale;
}
