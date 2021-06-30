<?php
namespace WordPressdotorg\Pattern_Translations\Cron;
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
	$makepot->import( true );
}
add_action( 'pattern_import_to_glotpress', __NAMESPACE__ . '\pattern_import_to_glotpress' );

/**
 * Sync/Create translated patterns of GlotPress translated patterns.
 *
 * This creates the "forked" patterns of a parent pattern when translations are available.
 */
function pattern_import_translations_to_directory() {
	foreach ( Pattern::get_patterns() as $pattern ) {
		foreach ( get_locales() as $gp_locale ) {
			$translated = $pattern->to_locale( $gp_locale->wp_locale );
			if ( $translated ) {
				create_or_update_translated_pattern( $translated );
			}
		}
	}
}
add_action( 'pattern_import_translations_to_directory', __NAMESPACE__ . '\pattern_import_translations_to_directory' );