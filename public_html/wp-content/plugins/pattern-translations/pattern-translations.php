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

use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\get_contains_block_types;
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

/**
 * Marks a translation that left `publish` because its original did.
 */
const STATUS_FROM_ORIGINAL = '_wpop_status_from_original';

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
 * @param Pattern   $pattern The translated pattern to store.
 * @param bool|null $written Optional. Set to whether the post or its terms were written.
 *
 * @return int|\WP_Error The pattern post ID, or an error if the content is refused or a write fails.
 */
function create_or_update_translated_pattern( Pattern $pattern, ?bool &$written = null ) {
	$written = false;

	if ( ! is_translated_content_allowed( $pattern->html ) ) {
		return new \WP_Error(
			'pattern_translation_disallowed_content',
			'Translated pattern content contains disallowed blocks or interactivity directives.'
		);
	}

	$parent = false;
	if ( $pattern->parent ) {
		// A chunk runs for minutes, so the cached status may be stale.
		clean_post_cache( $pattern->parent->ID );
		$parent = get_post( $pattern->parent->ID );

		if ( ! $parent || 'publish' !== $parent->post_status ) {
			return new \WP_Error(
				'pattern_translation_original_unpublished',
				'The original pattern is no longer published.'
			);
		}
	}

	$existing = $pattern->ID ? get_post( $pattern->ID ) : null;

	$args = array(
		'ID'                    => $pattern->ID,
		'post_type'             => POST_TYPE,
		'post_title'            => $pattern->title,
		'post_name'             => $pattern->ID ? $pattern->name : ( $pattern->name . '-' . $pattern->locale ), // TODO: Translate the slug?
		'post_date'             => $parent->post_date ?? '',
		'post_date_gmt'         => $parent->post_date_gmt ?? '',
		'post_content'          => $pattern->html,
		'post_parent'           => $pattern->parent->ID ?? 0,
		'post_author'           => $parent->post_author ?? 0,
		'post_status'           => $parent->post_status ?? 'pending',
		// Undo edits to fields the job doesn't translate.
		'post_excerpt'          => '',
		'post_content_filtered' => '',
		'post_password'         => '',
		'menu_order'            => 0,
		'comment_status'        => 'closed',
		'ping_status'           => 'closed',
		'to_ping'               => '',
		'pinged'                => '',
		'post_mime_type'        => '',
		'meta_input'            => array(
			'wpop_description'          => $pattern->description,
			'wpop_locale'               => $pattern->locale,
			'wpop_keywords'             => $pattern->keywords,
			'wpop_viewport_width'       => $parent->wpop_viewport_width ?? '',
			'wpop_block_types'          => $parent->wpop_block_types ?? '',
			// Matches what the `post_updated` hook stores.
			'wpop_contains_block_types' => get_contains_block_types( $pattern->html ),
			'wpop_wp_version'           => $parent->wpop_wp_version ?? '',
			'wpop_is_translation'       => true,
		),
	);

	if ( ! $args['ID'] ) {
		unset( $args['ID'] );
	}

	// Taken down by a moderator: new wording, same status.
	if ( $existing && ! is_following_original( $existing ) ) {
		if ( 'trash' === $existing->post_status ) {
			return $existing->ID;
		}

		$args['post_status'] = $existing->post_status;
	}

	// Each write fires the save hooks, and Jetpack Sync sends the whole post.
	if ( isset( $args['ID'] ) && is_stored_translation_current( $args ) ) {
		$post_id = $args['ID'];
	} else {
		/*
		 * `wp_insert_post()` expects slashed input and unslashes every field, `meta_input` included, before it
		 * writes. Nothing in $args arrives slashed (GlotPress strings, `get_post()` reads), so without this a
		 * literal backslash, or the `\u002d\u002d` the block serialiser writes for `--`, is stored one backslash short.
		 */
		$post_id = wp_insert_post( wp_slash( $args ), true );
		$written = ! is_wp_error( $post_id );
	}

	// Copy the terms from the parent if required.
	if ( $post_id && ! is_wp_error( $post_id ) && $pattern->parent ) {
		foreach ( array( 'wporg-pattern-category', 'wporg-pattern-keyword' ) as $taxonomy ) {
			$term_ids = get_term_ids( $pattern->parent->ID, $taxonomy );

			// Setting terms, even unchanged ones, invalidates every cached term query on the site.
			if ( get_term_ids( $post_id, $taxonomy ) === $term_ids ) {
				continue;
			}

			$result = wp_set_object_terms( $post_id, $term_ids, $taxonomy );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$written = true;
		}
	}

	return $post_id;
}

/**
 * Whether an existing translation already holds what writing $args would store.
 *
 * Values go through the same sanitizing as a write, so any difference it would make counts.
 *
 * @param array $args Unslashed `wp_insert_post()` arguments for an existing translation.
 * @return bool Whether the stored post and meta already match.
 */
function is_stored_translation_current( array $args ): bool {
	global $wpdb;

	$post = get_post( $args['ID'] );
	if ( ! $post ) {
		return false;
	}

	foreach ( array_diff_key( $args, array_flip( array( 'ID', 'meta_input' ) ) ) as $field => $value ) {
		$stored = wp_unslash( sanitize_post_field( $field, wp_slash( $value ), $post->ID, 'db' ) );

		// `wp_insert_post()` encodes emoji in these fields when the column can't hold them.
		if (
			in_array( $field, array( 'post_title', 'post_content', 'post_excerpt' ), true ) &&
			in_array( $wpdb->get_col_charset( $wpdb->posts, $field ), array( 'utf8', 'utf8mb3' ), true )
		) {
			$stored = wp_encode_emoji( $stored );
		}

		if ( (string) $stored !== (string) $post->$field ) {
			return false;
		}
	}

	foreach ( $args['meta_input'] as $key => $value ) {
		$value = sanitize_meta( $key, $value, 'post', POST_TYPE );

		// Raw, as `get_post_meta()` returns the registered default when there are no rows.
		$stored = get_metadata_raw( 'post', $post->ID, $key, false );

		// `update_post_meta()` sets every row of the key, or adds one when there are none.
		if ( ! is_scalar( $value ) || ! $stored ) {
			return false;
		}

		foreach ( $stored as $row ) {
			if ( (string) $value !== $row ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * A post's sorted term IDs in a taxonomy, via the object term cache.
 *
 * @param int    $post_id  Post ID.
 * @param string $taxonomy Taxonomy name.
 * @return int[] The sorted term IDs.
 */
function get_term_ids( int $post_id, string $taxonomy ): array {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( ! is_array( $terms ) ) {
		return array();
	}

	$term_ids = array_map( 'intval', wp_list_pluck( $terms, 'term_id' ) );
	sort( $term_ids );

	return $term_ids;
}

/**
 * The translations this pipeline stored for an English original, in any status.
 *
 * @param int $parent_id The English original's post ID.
 * @return int[] Translation post IDs.
 */
function get_translation_ids( int $parent_id ): array {
	if ( $parent_id <= 0 ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => POST_TYPE,
			'post_parent'    => $parent_id,
			// Not `any`, which leaves out trash.
			'post_status'    => array_keys( get_post_stati() ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'wpop_is_translation',
					'value' => 1,
				),
			),
		)
	);
}

/**
 * Give an English original's translations its new status.
 *
 * @param string   $new_status New post status.
 * @param string   $old_status Old post status.
 * @param \WP_Post $post       Post whose status changed.
 */
function sync_translation_statuses( $new_status, $old_status, $post ) {
	// Rescheduling keeps `future` but changes the date.
	$unchanged = $new_status === $old_status && 'future' !== $new_status;

	if ( $unchanged || 'new' === $old_status || POST_TYPE !== $post->post_type ) {
		return;
	}

	if ( get_post_meta( $post->ID, 'wpop_is_translation', true ) ) {
		return;
	}

	foreach ( get_translation_ids( $post->ID ) as $translation_id ) {
		set_translation_status( get_post( $translation_id ), $post );
	}
}
add_action( 'transition_post_status', __NAMESPACE__ . '\sync_translation_statuses', 10, 3 );

/**
 * Whether a translation follows its original's status: while it's public, or holds a status its original gave it.
 *
 * @param \WP_Post $translation The translation.
 * @return bool
 */
function is_following_original( \WP_Post $translation ): bool {
	return in_array( $translation->post_status, array( 'publish', 'future' ), true )
		|| (bool) get_post_meta( $translation->ID, STATUS_FROM_ORIGINAL, true );
}

/**
 * Whether `wp_insert_post()` keeps this status for this date, rather than switching `publish` and `future`.
 *
 * @param string $status   Post status.
 * @param string $date_gmt Post date, in GMT.
 * @return bool
 */
function status_holds_for_date( string $status, string $date_gmt ): bool {
	$ahead = strtotime( $date_gmt ) - time() >= MINUTE_IN_SECONDS;

	if ( 'future' === $status ) {
		return $ahead;
	}

	return 'publish' !== $status || ! $ahead;
}

/**
 * The translation the status sync is writing, so its writes aren't taken for a moderator's.
 *
 * @param int|null $translation_id Optional. The translation to record, or 0 once done.
 * @return int The translation being written, or 0.
 */
function translation_being_synced( ?int $translation_id = null ): int {
	static $current = 0;

	if ( null !== $translation_id ) {
		$current = $translation_id;
	}

	return $current;
}

/**
 * Give a translation its original's status and dates, or only report the change.
 *
 * @param \WP_Post $translation The translation.
 * @param \WP_Post $original    The translation's English original.
 * @param bool     $apply       Optional. Whether to change the translation, or only report. Default true.
 * @return string|null The change, with ", failed" when the write didn't take, or null when there is none.
 */
function set_translation_status( \WP_Post $translation, \WP_Post $original, bool $apply = true ): ?string {
	$from = $translation->post_status;
	$to   = $original->post_status;

	if ( ! is_following_original( $translation ) || ! status_holds_for_date( $to, $original->post_date_gmt ) ) {
		return null;
	}

	$same_dates = $translation->post_date === $original->post_date && $translation->post_date_gmt === $original->post_date_gmt;
	if ( $from === $to && ( 'trash' === $to || $same_dates ) ) {
		return null;
	}

	$change = $from === $to ? "{$to}, new date" : "{$from} to {$to}";
	if ( ! $apply ) {
		return $change;
	}

	translation_being_synced( $translation->ID );

	if ( 'trash' === $to ) {
		wp_trash_post( $translation->ID );
	} else {
		if ( 'trash' === $from ) {
			// Clears the trash meta, so the purge spares it.
			wp_untrash_post( $translation->ID );
		}

		wp_update_post(
			array(
				'ID'            => $translation->ID,
				'post_status'   => $to,
				'post_date'     => $original->post_date,
				'post_date_gmt' => $original->post_date_gmt,
			)
		);
	}

	translation_being_synced( 0 );

	if ( get_post_status( $translation->ID ) !== $to ) {
		return "{$change}, failed";
	}

	if ( in_array( $to, array( 'publish', 'future' ), true ) ) {
		delete_post_meta( $translation->ID, STATUS_FROM_ORIGINAL );
	} else {
		update_post_meta( $translation->ID, STATUS_FROM_ORIGINAL, 1 );
	}

	return $change;
}

/**
 * Drop the mark when anyone but the status sync changes a translation's status.
 *
 * @param string   $new_status New post status.
 * @param string   $old_status Old post status.
 * @param \WP_Post $post       Post whose status changed.
 */
function forget_status_from_original( $new_status, $old_status, $post ) {
	if ( $new_status === $old_status || translation_being_synced() === $post->ID ) {
		return;
	}

	if ( POST_TYPE === $post->post_type && get_post_meta( $post->ID, STATUS_FROM_ORIGINAL, true ) ) {
		delete_post_meta( $post->ID, STATUS_FROM_ORIGINAL );
	}
}
add_action( 'transition_post_status', __NAMESPACE__ . '\forget_status_from_original', 10, 3 );

/**
 * Trash an original's translations when it is deleted without going through the trash.
 *
 * @param int      $post_id Post ID.
 * @param \WP_Post $post    Post being deleted.
 */
function trash_translations_of_deleted_original( $post_id, $post ) {
	if ( POST_TYPE !== $post->post_type || get_post_meta( $post_id, 'wpop_is_translation', true ) ) {
		return;
	}

	foreach ( get_translation_ids( (int) $post_id ) as $translation_id ) {
		wp_trash_post( $translation_id );
	}
}
add_action( 'before_delete_post', __NAMESPACE__ . '\trash_translations_of_deleted_original', 10, 2 );

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
