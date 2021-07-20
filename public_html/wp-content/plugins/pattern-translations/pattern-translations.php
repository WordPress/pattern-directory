<?php
/**
 * Plugin Name: Pattern Translations
 * Description: Imports Pattern translations into GlotPress and provides translated patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 */

namespace WordPressdotorg\Pattern_Translations;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

const GLOTPRESS_PROJECT = 'patterns/core';
const TRANSLATED_BY_GLOTPRESS_KEY = '_glotpress_translated';

const TRANSLATED_TAXONOMIES = [
	// Taxonomy => Translation Context, see pattern-directory/bin/i18n.php
	'wporg-pattern-category'    => 'Categories term name',
	'wporg-pattern-flag-reason' => 'Flag Reasons term name',
];

require __DIR__ . '/includes/pattern.php';
require __DIR__ . '/includes/parser.php';
require __DIR__ . '/includes/i18n.php';
require __DIR__ . '/includes/makepot.php';
require __DIR__ . '/includes/cron.php';

if ( defined( 'WP_CLI' ) ) {
	require __DIR__ . '/includes/cli-commands.php';
}

/**
 * Creates or updates a localised pattern.
 */
function create_or_update_translated_pattern( Pattern $pattern ) {
	$args = [
		'ID'           => $pattern->ID,
		'post_type'    => POST_TYPE,
		'post_title'   => $pattern->title,
		'post_name'    => $pattern->ID ? $pattern->name : ( $pattern->name . '-' . $pattern->locale ), // TODO: Translate the slug?
		'post_date'    => $pattern->parent ? get_post( $pattern->parent->ID )->post_date : '',
		'post_content' => $pattern->html,
		'post_parent'  => $pattern->parent ? $pattern->parent->ID : 0,
		'post_author'  => $pattern->parent ? get_post( $pattern->parent->ID )->post_author : 0,
		'post_status'  => $pattern->parent ? get_post( $pattern->parent->ID )->post_status : 'pending',
		'meta_input'   => [
			'wpop_description'    => $pattern->description,
			'wpop_locale'         => $pattern->locale,
			'wpop_is_translation' => true,
		],
	];

	if ( ! $args['ID'] ) {
		unset( $args['ID'] );
	}

	$post_id = wp_insert_post( $args, true );

	// Copy the terms from the parent if required.
	if ( $post_id && ! is_wp_error( $post_id ) && $pattern->parent ) {
		foreach ( [ 'wporg-pattern-category', 'wporg-pattern-keyword' ] as $taxonomy ) {
			$term_ids = wp_get_object_terms( $pattern->parent->ID, $taxonomy, [ 'fields' => 'ids' ] );
			wp_set_object_terms( $post_id, $term_ids, $taxonomy );
		}
	}

	return $post_id;
}

/**
 * Translate term names into the current site locale.
 *
 * @param WP_Term $term The WP_Term object being loaded.
 */
function translate_term( $term ) {
	if (
		is_admin() ||
		// Not get_user_locale(), as we respect the displayed site locale.
		'en_US' === get_locale() ||
		// Only certain translated taxonomies
		! isset( TRANSLATED_TAXONOMIES[ $term->taxonomy ] )
	) {
		return $term;
	}

	$i18n_context = TRANSLATED_TAXONOMIES[ $term->taxonomy ];
	$term->name   = esc_html( translate_with_gettext_context( html_entity_decode( $term->name ), $i18n_context, 'wporg-patterns' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText

	return $term;
}
add_filter( 'get_term', __NAMESPACE__ . '\translate_term' );

/**
 * Set the correct locale context for API endpoints.
 *
 * For api.wordpress.org requests, the `locale` GET parameter is respected if set. Defaults to en_US otherwise.
 * For REST API requests, the `_locale=user` GET parameter is ignored for authenticated requests, causing the rest to default to the Site locale.
 */
function locale( $locale ) {
	// When being requested through api.wordpress.org, respect the query variable.
	if (
		defined( 'WPORG_IS_API' ) &&
		WPORG_IS_API &&
		! empty( $_GET['locale'] )
	) {
		return $_GET['locale'];
	}

	// Respect the site locale otherwise for rest api queries.
	// This is used to prevent `?_locale=user` returning non-translated details on localised sites.
	if (
		wp_is_json_request() &&
		isset( $_GET['_locale'] ) &&
		'user' === $_GET['_locale']
	) {
		$_GET['_locale'] = 'site';
	}

	return $locale;
}
add_filter( 'locale', __NAMESPACE__ . '\locale' );
