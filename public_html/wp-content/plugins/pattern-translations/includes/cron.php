<?php
namespace WordPressdotorg\Pattern_Translations\Cron;
use WordPressdotorg\Pattern_Translations\{ Pattern, PatternMakepot };
use function WordPressdotorg\Pattern_Translations\create_or_update_translated_pattern;
use function WordPressdotorg\Locales\get_locales;

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
	echo $makepot->import( true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
		$pattern_ids = Pattern::get_patterns( [ 'fields' => 'ids' ] );

		if ( wp_doing_cron() ) {
			// Chunk the patterns to avoid memory exhaustion.
			$timestamp = time();
			$chunks    = array_chunk( $pattern_ids, CHUNK_SIZE );
			// Spread out the sub-tasks over the entire twicedaily period.
			$delay     = floor( ( 12 * HOUR_IN_SECONDS ) / count( $chunks ) );
			foreach ( $chunks as $chunk ) {
				wp_schedule_single_event( $timestamp, current_action(), array( $chunk ) );

				$timestamp += $delay;
			}

			printf( "Queued %d cron jobs of %d Patterns each.\n", count( $pattern_ids ) / CHUNK_SIZE, CHUNK_SIZE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
	}

	// See https://github.com/WordPress/gutenberg/issues/59300
	remove_action( 'registered_post_type', 'gutenberg_block_core_navigation_link_register_post_type_variation' );
	remove_action( 'registered_taxonomy', 'gutenberg_block_core_navigation_link_register_taxonomy_variation' );

	// Raise the memory limit for this process to at least 512M.
	add_filter(
		'cron_memory_limit',
		function() {
			return '512M';
		}
	);
	wp_raise_memory_limit( 'cron' );

	$locales = get_locales();

	printf( "Processing %d Patterns in %d locales.\n", count( $pattern_ids ), count( $locales ) );

	foreach ( $pattern_ids as $i => $pattern_id ) {
		$pattern = Pattern::from_post( get_post( $pattern_id ) );

		echo "{$i}. Processing {$pattern->name} / '{$pattern->title}'..\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		foreach ( $locales as $gp_locale ) {
			$locale     = $gp_locale->wp_locale;
			if ( ! $locale || 'en_US' === $locale ) {
				continue;
			}

			$translated = $pattern->to_locale( $locale );
			if ( $translated ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "\t{$locale} - " . ( $translated->ID ? 'Updating' : 'Creating' ) . " Translated pattern.\n";
				create_or_update_translated_pattern( $translated );
			} else {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "\t{$locale} - No Translations exist yet.\n";
				// TODO: Note: There may exist a translated pattern using old strings.
				// Considering this as an edge-case that is unlikely and we don't
				// need to handle. Serving old Translated template is better in this case.
			}
		}

		// Clear memory-heavy variables after each iteration of Patterns, to avoid object cache memory exhaustion.
		clear_memory_heavy_variables();
	}
}
add_action( 'pattern_import_translations_to_directory', __NAMESPACE__ . '\pattern_import_translations_to_directory' );

/**
 * Clear caches for memory management.
 *
 * @static
 * @global \wpdb            $wpdb
 * @global \WP_Object_Cache $wp_object_cache
 */
function clear_memory_heavy_variables() {
	global $wpdb, $wp_object_cache;

	$wpdb->queries = [];

	if ( is_object( $wp_object_cache ) ) {
		$wp_object_cache->cache          = [];
		$wp_object_cache->group_ops      = [];
		$wp_object_cache->memcache_debug = [];
	}
}
