<?php
/**
 * Scheduled pattern translation jobs.
 *
 * @package WordPressdotorg\Pattern_Translations
 */

namespace WordPressdotorg\Pattern_Translations\Cron;

use WP_CLI;
use WordPressdotorg\Pattern_Translations\{ Pattern, PatternMakepot };
use function WordPressdotorg\Pattern_Translations\create_or_update_translated_pattern;
use function WordPressdotorg\Locales\get_locales;
use const WordPressdotorg\Pattern_Translations\GLOTPRESS_PROJECT;

const CHUNK_SIZE = 50;

/**
 * Register the cron jobs needed.
 */
function register_cron_tasks() {
	if ( ! wp_next_scheduled( 'pattern_import_to_glotpress' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'pattern_import_to_glotpress' );
	}

	if ( ! wp_next_scheduled( 'pattern_import_translations_to_directory' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'pattern_import_translations_to_directory' );
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\register_cron_tasks' );

/**
 * Periodically import all Patterns into GlotPress for translation.
 *
 * This is the equivalent of the following WP-CLI command:
 * `wp --url=https://wordpress.org/patterns/ patterns glotpress-import --all-posts --save`
 */
function pattern_import_to_glotpress() {
	$patterns = Pattern::get_patterns();
	$makepot  = new PatternMakepot( $patterns );
	$result   = $makepot->import( true );
	log_message( $result );
}
add_action( 'pattern_import_to_glotpress', __NAMESPACE__ . '\pattern_import_to_glotpress' );

/**
 * Sync/Create translated patterns of GlotPress translated patterns.
 *
 * This creates the "forked" patterns of a parent pattern when translations are available.
 * This queues sub-tasks which each process a CHUNK_SIZE group of patterns, to avoid memory exhaustion.
 * These subtasks are spread between now and the next time this cron is expected to run.
 *
 * @param int[] $pattern_ids Optional. An array of Pattern IDs to process.
 *                           If not provided, queues sub-tasks if in cron context, else processes all patterns.
 */
function pattern_import_translations_to_directory( $pattern_ids = array() ) {
	if ( ! $pattern_ids ) {
		$pattern_ids = Pattern::get_patterns( array( 'fields' => 'ids' ) );

		if ( wp_doing_cron() ) {
			// Chunk the patterns to avoid memory exhaustion.
			$timestamp = time();
			$chunks    = array_chunk( $pattern_ids, CHUNK_SIZE );
			// Spread out the sub-tasks over the entire twicedaily period.
			$delay = floor( ( 12 * HOUR_IN_SECONDS ) / count( $chunks ) );
			foreach ( $chunks as $chunk ) {
				wp_schedule_single_event( $timestamp, current_action(), array( $chunk ) );

				$timestamp += $delay;
			}

			log_message( sprintf( 'Queued %d cron jobs of %d Patterns each.', count( $pattern_ids ) / CHUNK_SIZE, CHUNK_SIZE ) );
			return;
		}
	}

	// See https://github.com/WordPress/gutenberg/issues/59300.
	remove_action( 'registered_post_type', 'gutenberg_block_core_navigation_link_register_post_type_variation' );
	remove_action( 'registered_taxonomy', 'gutenberg_block_core_navigation_link_register_taxonomy_variation' );

	// Raise the memory limit for this process to at least 512M.
	add_filter(
		'cron_memory_limit',
		function () {
			return '512M';
		}
	);
	wp_raise_memory_limit( 'cron' );

	$locales = get_locales();

	// A locale without a single translation can't translate any pattern, so skip it entirely.
	$translated_locales = get_translated_locales();
	if ( $translated_locales ) {
		$locales = array_intersect_key( $locales, array_flip( $translated_locales ) );
	}

	log_message( sprintf( 'Processing %d Patterns in %d locales.', count( $pattern_ids ), count( $locales ) ) );

	foreach ( $pattern_ids as $i => $pattern_id ) {
		$pattern = Pattern::from_post( get_post( $pattern_id ) );

		log_message( "{$i}. Processing {$pattern->name} / '{$pattern->title}'.." );
		foreach ( $locales as $gp_locale ) {
			$locale = $gp_locale->wp_locale;
			if ( ! $locale || 'en_US' === $locale ) {
				continue;
			}

			$translated = $pattern->to_locale( $locale );
			if ( $translated ) {
				$result = create_or_update_translated_pattern( $translated, $written );
				if ( is_wp_error( $result ) ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- failures need to reach the server log, not only the job output.
					error_log( "Pattern translation import failed for {$pattern->name} ({$locale}): " . $result->get_error_message() );
					log_message( "\t{$locale} - ERROR: {$result->get_error_message()}" );
				} elseif ( ! $written ) {
					log_message( "\t{$locale} - Translated pattern unchanged." );
				} else {
					log_message( "\t{$locale} - " . ( $translated->ID ? 'Updated' : 'Created' ) . ' Translated pattern.' );
				}
			} else {
				log_message( "\t{$locale} - No Translations exist yet." );

				/*
				 * TODO: Note: There may exist a translated pattern using old strings.
				 * Considering this as an edge-case that is unlikely and we don't
				 * need to handle. Serving old Translated template is better in this case.
				 */
			}

			// A single pattern across all locales can exhaust memory, so clear after each locale.
			clear_memory_heavy_variables();
		}
	}
}
add_action( 'pattern_import_translations_to_directory', __NAMESPACE__ . '\pattern_import_translations_to_directory' );

/**
 * The WordPress locales with at least one current translation in the patterns GlotPress project.
 *
 * Uses the same conditions as `GlotPress_Translate_Bridge`, so a locale missing here can't translate any string.
 *
 * @return string[] WordPress locales, or an empty array if GlotPress isn't available.
 */
function get_translated_locales() {
	global $wpdb;

	if ( ! class_exists( 'GP_Locales' ) ) {
		return array();
	}

	$prefix = defined( 'GLOTPRESS_TABLE_PREFIX' ) ? GLOTPRESS_TABLE_PREFIX : 'gp_';

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic table prefix cannot be passed via placeholders.
	$sets = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT s.locale, s.slug
			FROM {$prefix}translation_sets s
			INNER JOIN {$prefix}projects p ON p.id = s.project_id
			WHERE p.path = %s AND EXISTS (
				SELECT 1
				FROM {$prefix}translations t
				INNER JOIN {$prefix}originals o ON o.id = t.original_id
				WHERE t.translation_set_id = s.id AND t.status = 'current' AND o.status = '+active'
			)",
			GLOTPRESS_PROJECT
		)
	);
	// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	$wp_locales = array();
	foreach ( (array) $sets as $set ) {
		// Variants such as `de/formal` are locales of their own.
		$gp_locale = \GP_Locales::by_slug( 'default' === $set->slug ? $set->locale : "{$set->locale}/{$set->slug}" );
		if ( $gp_locale && $gp_locale->wp_locale ) {
			$wp_locales[] = $gp_locale->wp_locale;
		}
	}

	return $wp_locales;
}

/**
 * Clear caches for memory management.
 *
 * @static
 * @global \wpdb $wpdb
 */
function clear_memory_heavy_variables() {
	global $wpdb;

	$wpdb->queries = array();

	wp_cache_flush_runtime();
}

/**
 * Output a progress message.
 *
 * Cavalcade runs jobs without WP-CLI and records their output, so echo when WP-CLI isn't available.
 *
 * @param string $message The message to output.
 */
function log_message( $message ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $message );
	} else {
		echo $message . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain-text job output.
	}
}
