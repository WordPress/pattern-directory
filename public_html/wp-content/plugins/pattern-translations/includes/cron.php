<?php
namespace WordPressdotorg\Pattern_Translations\Cron;
use WordPressdotorg\Pattern_Translations\{ Pattern, PatternMakepot };
use function WordPressdotorg\Pattern_Translations\create_or_update_translated_pattern;
use function WordPressdotorg\Locales\get_locales;

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
 * This is the equivilient of the following WP-CLI command:
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
 */
function pattern_import_translations_to_directory() {
	$patterns = Pattern::get_patterns();
	$locales  = get_locales();

	printf( "Processing %d Patterns in %d locales.\n", count( $patterns ), count( $locales ) );

	foreach ( $patterns as $pattern ) {
		echo "Processing {$pattern->name} / '{$pattern->title}'..\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
