<?php
namespace WordPressdotorg\Pattern_Translations;

/**
 * Translate an array of patterns into the current locale.
 *
 * @param array $patterns An array of Pattern objects to translate.
 * @return array The translated pattern objects.
 */
function translate_patterns( array $patterns ) : array {
	return translate_patterns_to( $patterns, get_locale() );
}

/**
 * Translate an array of patterns to a specific locale.
 *
 * @param array  $patterns The array of Pattern objects to translate.
 * @param string $locale   The locale to translate into.
 * @return array The translated pattern objects.
 */
function translate_patterns_to( array $patterns, string $locale ) : array {
	return array_map( function( $pattern ) use ( $locale ) {
		return $pattern->to_locale( $locale ) ?: $pattern;
	}, $patterns );
}
