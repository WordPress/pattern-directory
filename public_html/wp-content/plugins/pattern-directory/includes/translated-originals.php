<?php
/**
 * List a translated pattern once when browsing a locale, in place of its English original.
 *
 * @package WordPressdotorg\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Translated_Originals;

use WP_Post;
use WP_Query;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Private taxonomy labelling an English original with each locale it has a published translation into.
 */
const TAXONOMY = 'wporg-pattern-translated';

/**
 * Register the taxonomy. It's internal: no UI, REST field, query var or archive.
 */
function register_translated_taxonomy(): void {
	register_taxonomy(
		TAXONOMY,
		POST_TYPE,
		array(
			'public'       => false,
			'rewrite'      => false,
			'query_var'    => false,
			'show_in_rest' => false,
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\register_translated_taxonomy' );

/**
 * Leave out English originals that have a published translation into the requested locale.
 *
 * Opt-in through the `hide_translated_originals` query var, so lists of specific patterns, like favorites, keep
 * the originals they hold. Searches keep both, so an English query still finds the original. So does the Popular
 * sort, which only lists patterns with a favorite count, and translations rarely have one.
 *
 * The pipeline copies the original's author, status, date, terms and version onto its translations, so a
 * translation passes the same filters and is listed instead.
 *
 * @param string[] $clauses The query's SQL clauses.
 * @param WP_Query $query   The query.
 * @return string[] The clauses, excluding translated originals when requested.
 */
function filter_clauses( array $clauses, WP_Query $query ): array {
	global $wpdb;

	if ( ! $query->get( 'hide_translated_originals' ) || $query->is_search() || 'wporg-pattern-favorites' === $query->get( 'meta_key' ) ) {
		return $clauses;
	}

	$locale = $query->meta_query->queries['orderby_locale']['value'][0] ?? '';
	if ( ! is_string( $locale ) || '' === $locale || 'en_US' === $locale ) {
		return $clauses;
	}

	$term = get_term_by( 'name', $locale, TAXONOMY );
	if ( ! $term ) {
		return $clauses;
	}

	// A subquery rather than a `tax_query`, which a request's `tax_relation=OR` would turn into "or not translated".
	$clauses['where'] .= $wpdb->prepare(
		" AND {$wpdb->posts}.ID NOT IN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d )",
		$term->term_taxonomy_id
	);

	return $clauses;
}
add_filter( 'posts_clauses', __NAMESPACE__ . '\filter_clauses', PHP_INT_MAX, 2 );

/**
 * Label a translation's original with its locale when the translation is published, and unlabel it when it's unpublished.
 *
 * Each label change resets the cached post queries: WP_Query keys them on posts alone unless it has a `tax_query`.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post whose status changed.
 */
function update_on_status_change( string $new_status, string $old_status, WP_Post $post ): void {
	if ( $new_status === $old_status || ! in_array( 'publish', array( $new_status, $old_status ), true ) ) {
		return;
	}

	$locale = get_translation_locale( $post );
	if ( ! $locale ) {
		return;
	}

	if ( 'publish' === $new_status ) {
		wp_add_object_terms( (int) $post->post_parent, $locale, TAXONOMY );
		wp_cache_set_posts_last_changed();
	} else {
		unlabel_unless_translated( (int) $post->post_parent, $locale );
	}
}
add_action( 'transition_post_status', __NAMESPACE__ . '\update_on_status_change', 10, 3 );

/**
 * Unlabel a published translation's original once the translation is deleted outright.
 *
 * The locale is read before deletion, as core removes the meta first, and the label is removed after, once the
 * translation no longer counts as published.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post being deleted.
 */
function update_on_delete( int $post_id, WP_Post $post ): void {
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
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array(
					'key'   => 'wpop_locale',
					'value' => $locale,
				),
			),
		)
	);

	if ( ! $remaining ) {
		wp_remove_object_terms( $original_id, $locale, TAXONOMY );
		wp_cache_set_posts_last_changed();
	}
}

/**
 * The locale of a translated pattern.
 *
 * Only the translation pipeline gives a pattern a parent: authors can't set one over REST.
 *
 * @param WP_Post $post The pattern.
 * @return string The locale, or an empty string if the pattern isn't a translation.
 */
function get_translation_locale( WP_Post $post ): string {
	if ( POST_TYPE !== $post->post_type || ! $post->post_parent ) {
		return '';
	}

	$locale = (string) get_post_meta( $post->ID, 'wpop_locale', true );

	return 'en_US' === $locale ? '' : $locale;
}

/**
 * Give every English original exactly the labels of the locales it has published translations into.
 *
 * Labels originals translated before this taxonomy existed, and repairs any that fell out of step.
 *
 * @param bool $apply Whether to change anything, or only count.
 * @return int[] The number of originals checked and of those whose labels changed.
 */
function sync_all( bool $apply ): array {
	global $wpdb;

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT translation.post_parent AS original_id, translation_locale.meta_value AS locale
			FROM {$wpdb->posts} AS translation
			STRAIGHT_JOIN {$wpdb->postmeta} AS translation_locale ON ( translation_locale.post_id = translation.ID AND translation_locale.meta_key = 'wpop_locale' )
			STRAIGHT_JOIN {$wpdb->posts} AS original ON ( original.ID = translation.post_parent AND original.post_type = %s )
			WHERE translation.post_type = %s AND translation.post_status = 'publish' AND translation.post_parent > 0",
			POST_TYPE,
			POST_TYPE
		)
	);

	$expected = array();
	foreach ( $rows as $row ) {
		if ( 'en_US' !== $row->locale ) {
			$expected[ (int) $row->original_id ][] = $row->locale;
		}
	}

	// Originals labelled now, so labels whose translations are gone are removed too.
	$labelled = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT relationship.object_id
			FROM {$wpdb->term_relationships} AS relationship
			INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy ON ( term_taxonomy.term_taxonomy_id = relationship.term_taxonomy_id )
			WHERE term_taxonomy.taxonomy = %s",
			TAXONOMY
		)
	);

	$original_ids = array_unique( array_merge( array_keys( $expected ), array_map( 'intval', $labelled ) ) );
	$counts       = array(
		'checked' => count( $original_ids ),
		'changed' => 0,
	);

	foreach ( array_values( $original_ids ) as $i => $original_id ) {
		$locales = array_unique( $expected[ $original_id ] ?? array() );
		$current = wp_get_object_terms( $original_id, TAXONOMY, array( 'fields' => 'names' ) );

		sort( $locales );
		$current = is_wp_error( $current ) ? array() : $current;
		sort( $current );

		if ( $locales !== $current ) {
			++$counts['changed'];

			if ( $apply ) {
				wp_set_object_terms( $original_id, $locales, TAXONOMY );
			}
		}

		if ( 0 === ( $i + 1 ) % 500 ) {
			wp_cache_flush_runtime();
		}
	}

	// Listings are cached by the posts' last change, which a change of labels alone doesn't move.
	if ( $apply && $counts['changed'] ) {
		wp_cache_set_posts_last_changed();
	}

	return $counts;
}
