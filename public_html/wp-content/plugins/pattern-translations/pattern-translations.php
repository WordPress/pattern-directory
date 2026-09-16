<?php
/**
 * Plugin Name: Pattern Translations
 * Description: Imports Pattern translations into GlotPress and provides translated patterns.
 * Plugin URI:  https://wordpress.org/patterns/
 * Text Domain: wporg-plugins
 *
 * @package WordPressdotorg\Pattern_Translations
 */

namespace WordPressdotorg\Pattern_Translations;

use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\is_block_allowed_in_pattern;
use function WordPressdotorg\Pattern_Directory\Pattern_Validation\content_has_block_directives;
use function WordPressdotorg\Pattern_Directory\Pattern_Validation\blocks_have_directive_attribute;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * GlotPress project containing pattern strings.
 */
const GLOTPRESS_PROJECT = 'patterns/core';

/**
 * Taxonomies and their translation contexts.
 */
const TRANSLATED_TAXONOMIES = array(
	// Taxonomy => Translation Context, see pattern-directory/bin/i18n.php.
	'wporg-pattern-category'    => 'Categories term name',
	'wporg-pattern-flag-reason' => 'Flag Reasons term name',
);

require __DIR__ . '/includes/class-pattern.php';
require __DIR__ . '/includes/class-patternparser.php';
require __DIR__ . '/includes/i18n.php';
require __DIR__ . '/includes/class-patternmakepot.php';
require __DIR__ . '/includes/cron.php';

if ( defined( 'WP_CLI' ) ) {
	require __DIR__ . '/includes/class-wp-cli-patterns.php';
}

/**
 * Whether translated pattern HTML stays within what the directory accepts from direct submissions.
 *
 * Translator-supplied strings are assembled into stored markup without passing through the REST
 * validators, so the block allowlist and Interactivity-directive checks (the two a translated string
 * could realistically violate) run here. The remaining REST checks still apply to the English parent.
 *
 * @param string $html The assembled, translated pattern HTML.
 * @return bool Whether the HTML is safe to store as a pattern.
 */
function is_translated_content_allowed( $html ) {
	// The job runs without a user, so the save-time filters change the bytes; check what the row will hold as well as the assembled form.
	$stored = wp_unslash( sanitize_post_field( 'post_content', wp_slash( $html ), 0, 'db' ) );
	foreach ( array_unique( array( $html, $stored ) ) as $markup ) {
		$blocks = parse_blocks( $markup );
		while ( $blocks ) {
			$block = array_shift( $blocks );

			if ( ! is_null( $block['blockName'] ) && ! is_block_allowed_in_pattern( $block['blockName'] ) ) {
				return false;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks = array_merge( $blocks, $block['innerBlocks'] );
			}
		}

		// Translated strings land in block attributes as well as inner HTML, and neither is sanitised by KSES.
		if ( content_has_block_directives( $markup ) || blocks_have_directive_attribute( parse_blocks( $markup ) ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Creates or updates a localised pattern.
 *
 * @param Pattern $pattern The translated pattern to store.
 *
 * @return int|\WP_Error The pattern post ID, or an error if the content is refused or the write fails.
 */
function create_or_update_translated_pattern( Pattern $pattern ) {
	if ( ! is_translated_content_allowed( $pattern->html ) ) {
		return new \WP_Error(
			'pattern_translation_disallowed_content',
			'Translated pattern content contains disallowed blocks or interactivity directives.'
		);
	}

	$parent = false;
	if ( $pattern->parent ) {
		$parent = get_post( $pattern->parent->ID );
	}

	$args = array(
		'ID'           => $pattern->ID,
		'post_type'    => POST_TYPE,
		'post_title'   => $pattern->title,
		'post_name'    => $pattern->ID ? $pattern->name : ( $pattern->name . '-' . $pattern->locale ), // TODO: Translate the slug?
		'post_date'    => $parent->post_date ?? '',
		'post_content' => $pattern->html,
		'post_parent'  => $pattern->parent->ID ?? 0,
		'post_author'  => $parent->post_author ?? 0,
		'post_status'  => $parent->post_status ?? 'pending',
		'meta_input'   => array(
			'wpop_description'          => $pattern->description,
			'wpop_locale'               => $pattern->locale,
			'wpop_keywords'             => $pattern->keywords,
			'wpop_viewport_width'       => $parent->wpop_viewport_width ?? '',
			'wpop_block_types'          => $parent->wpop_block_types ?? '',
			'wpop_contains_block_types' => $parent->wpop_contains_block_types ?? '',
			'wpop_wp_version'           => $parent->wpop_wp_version ?? '',
			'wpop_is_translation'       => true,
		),
	);

	if ( ! $args['ID'] ) {
		unset( $args['ID'] );
	}

	/*
	 * `wp_insert_post()` expects slashed input and unslashes every field, `meta_input` included, before it
	 * writes. Nothing in $args arrives slashed (GlotPress strings, `get_post()` reads), so without this a
	 * literal backslash, or the `\u002d\u002d` the block serialiser writes for `--`, is stored one backslash short.
	 */
	$post_id = wp_insert_post( wp_slash( $args ), true );

	// Copy the terms from the parent if required.
	if ( $post_id && ! is_wp_error( $post_id ) && $pattern->parent ) {
		foreach ( array( 'wporg-pattern-category', 'wporg-pattern-keyword' ) as $taxonomy ) {
			$term_ids = wp_get_object_terms( $pattern->parent->ID, $taxonomy, array( 'fields' => 'ids' ) );
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
		// Only certain translated taxonomies.
		! isset( TRANSLATED_TAXONOMIES[ $term->taxonomy ] )
	) {
		return $term;
	}

	$i18n_context = TRANSLATED_TAXONOMIES[ $term->taxonomy ];
	$term->name   = esc_html( translate_with_gettext_context( html_entity_decode( $term->name ), $i18n_context, 'wporg-patterns' ) ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.NonSingularStringLiteralContext, WordPress.WP.I18n.LowLevelTranslationFunction -- Stored taxonomy names require dynamic translation text and contexts.

	return $term;
}
add_filter( 'get_term', __NAMESPACE__ . '\translate_term' );

/**
 * Translate the title of pages.
 *
 * @param string $title   The current title, ignored.
 * @param int    $post_id The post_id of the page.
 * @return string Possibly translated page title.
 */
function translate_page_title( $title, $post_id = null ) {
	$post = get_post( $post_id );

	if ( $post && 'page' === $post->post_type ) {
		$title = translate_with_gettext_context( $post->post_title, 'Page title', 'wporg-patterns' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.WP.I18n.LowLevelTranslationFunction -- Stored page titles require dynamic translation text.
	}

	return $title;
}
add_filter( 'the_title', __NAMESPACE__ . '\translate_page_title', 1, 2 );
add_filter( 'single_post_title', __NAMESPACE__ . '\translate_page_title', 1, 2 );

/**
 * Set the correct locale context for API endpoints.
 *
 * For api.wordpress.org requests, the `locale` GET parameter is respected if set. Defaults to en_US otherwise.
 * For REST API requests, the `_locale=user` GET parameter is ignored for authenticated requests, causing the rest to default to the Site locale.
 *
 * @param string $locale Current locale.
 * @return string Negotiated locale.
 */
function locale( $locale ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- locale negotiation on GET; nothing is persisted, and the only write is to $_GET itself for the current request.

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- $raw_locale is the untouched baseline the next line is compared against.
	$raw_locale  = isset( $_GET['locale'] ) && is_string( $_GET['locale'] ) ? wp_unslash( $_GET['locale'] ) : '';
	$safe_locale = sanitize_locale_name( $raw_locale );

	// When being requested through api.wordpress.org, respect the query variable.
	if (
		defined( 'WPORG_IS_API' ) &&
		WPORG_IS_API &&
		! empty( $raw_locale ) &&
		$safe_locale === $raw_locale
	) {
		return $safe_locale;
	}

	/*
	 * Respect the site locale otherwise for rest api queries.
	 * This is used to prevent `?_locale=user` returning non-translated details on localised sites.
	 */
	if (
		wp_is_json_request() &&
		isset( $_GET['_locale'] ) &&
		'user' === $_GET['_locale']
	) {
		$_GET['_locale'] = 'site';
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	return $locale;
}
add_filter( 'locale', __NAMESPACE__ . '\locale' );
