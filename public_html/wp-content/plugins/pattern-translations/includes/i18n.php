<?php
namespace WordPressdotorg\Pattern_Translations;
use GlotPress_Translate_Bridge;

/**
 * Translate an array of patterns into the current locale.
 *
 * @param array $patterns An array of Pattern objects to translate.
 * @return array The translated pattern objects.
 */
function translate_patterns( array $patterns ) : array {
	$translated_patterns = [];

	if ( 'en_US' === get_locale() ) {
		return $patterns;
	}

	foreach ( $patterns as $pattern ) {
		$parser = new PatternParser( $pattern );

		$replacements = [];

		foreach ( $parser->to_strings() as $string ) {
			if ( 'strrev' === get_locale() ) {
				$replacements[ $string ] = strrev( $string );
			} else {
				$replacements[ $string ] = GlotPress_Translate_Bridge::translate( $string, GLOTPRESS_PROJECT );
			}
		}

		$translated_patterns[] = $parser->replace_strings_with_kses( $replacements );
	}

	return $translated_patterns;
}

/**
 * Translate an array of patterns to a specific locale.
 *
 * @param array  $patterns The array of Pattern objects to translate.
 * @param string $locale   The locale to translate into.
 * @return array The translated pattern objects.
 */
function translate_patterns_to( array $patterns, string $locale ) : array {
	try {
		switch_to_locale( $locale );

		return translate_patterns( $patterns );
	} finally {
		restore_current_locale();
	}
}

add_filter( 'get_available_languages', function( $languages ) {
	$languages[] = 'strrev';
	return $languages;
} );