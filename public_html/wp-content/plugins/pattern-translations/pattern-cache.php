<?php

namespace WordPressdotorg\Pattern_Translations;

/**
 *
 *
 * @param string $site domain of the site
 * @param array $query
 * @param string $locale
 *
 * @return string
 */
function get_patterns_request_cache_key( string $site, array $query, string $locale ) : string {
	return 'ptk_patterns_endpoint_' . $site . '_' . md5( http_build_query( $query ) ) . '_' . $locale;
}

/**
 * This is a helper function to get back all the layout patterns from dotcompatterns.wordpress.com.
 *
 * @param array $args
 * @param string $locale
 *
 * @return WP_Error|array
 */
function get_page_layouts_patterns( $args = [], string $locale = '' ) {
	// This makes this function easier to test.
	$site_id = apply_filters( 'page_layouts_pattern_site_id', 174455321 ); // dotcompatterns.wordpress.com

	$defaults = [
		'tag' => [ 'layout' ],
		'posts_per_page' => -1,
	];

	return get_cached_patterns( wp_parse_args( $args, $defaults ), $site_id, $locale );
}

/**
 * Helper function that returns patterns from cache or an error.
 *
 * @param array $query_args
 * @param $site_id
 * @param string $locale
 *
 * @return WP_Error|array
 */
function get_cached_patterns( $query_args = [], $site_id, $locale = '' ) {
	$site_details = get_blog_details( $site_id );

	if ( false === \get_lang_name_by_code( $locale ) ) {
		return new \WP_Error( 'invalid_locale', "Invalid locale: $locale", 400 );
	}

	if ( ! isset( $site_details->domain ) ) {
		return new \WP_Error( 'no_site', 'No such site', 500 );
	}

	$cache_key = get_patterns_request_cache_key( $site_details->domain, $query_args, $locale );
	$patterns  = \wp_cache_get( $cache_key, 'ptk_patterns' );

	if ( false === $patterns ) {
		$store = PatternStores::get_store( $site_id );  // dotcompatterns.wordpress.com

		if ( empty( $store ) ) {
			return new \WP_Error( 'no_store', 'No such store', 500 );
		}

		$patterns = $store->get_patterns( $query_args );

		if ( ! empty( $patterns ) ) {
			$translator = new PatternsTranslator( $patterns, $locale );
			$patterns   = $translator->translate();
		}

		\wp_cache_add( $cache_key, $patterns, 'ptk_patterns', 5 * MINUTE_IN_SECONDS );
	}

	if ( empty( $patterns ) ) {
		return new \WP_Error( 'no_patterns', 'Not found', 404 );
	}

	return $patterns;
}
