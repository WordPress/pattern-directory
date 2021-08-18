<?php

namespace WordPressdotorg\Pattern_Directory\Search;

use WP_Query, Jetpack_WPES_Search_Query_Parser;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

// Note: This depends on the Search module and custom index being enabled in `mu-plugins/main-network/site-patterns.php`.
// There are e2e tests on the api.w.org endpoint, rather than unit tests in this plugin. Make sure you run those when making changes.

add_filter( 'jetpack_search_should_handle_query', __NAMESPACE__ . '\should_handle_query', 10, 2 );
add_filter( 'jetpack_search_es_query_args', __NAMESPACE__ . '\modify_es_query_args', 10, 2 );
add_filter( 'jetpack_sync_post_meta_whitelist', __NAMESPACE__ . '\sync_pattern_meta' );
add_action( 'jetpack_search_abort', __NAMESPACE__ . '\log_aborted_queries', 10, 2 );
add_action( 'failed_jetpack_search_query', __NAMESPACE__ . '\log_failed_queries' );


/**
 * Tell Jetpack to sync pattern meta, so it can be indexed by ElasticSearch.
 *
 * @param array $post_meta_safelist
 *
 * @return array
 */
function sync_pattern_meta( $post_meta_safelist ) {
	$post_meta_safelist[] = 'wpop_description';
	$post_meta_safelist[] = 'wpop_viewport_width';
	$post_meta_safelist[] = 'wpop_locale';

	return $post_meta_safelist;
}

/**
 * Determine if the search query should be handled by Jetpack/ElasticSearch.
 *
 * Our ES query modifications should only affect the REST API (which includes the front end, since the theme makes
 * XHR requests to fetch patterns). We don't want to restrict wp-admin list tables, since that could make it
 * difficult to find variations, etc.
 *
 * @param bool     $handle_query
 * @param WP_Query $query
 *
 * @return bool
 */
function should_handle_query( $handle_query, $query ) {
	/*
	 * This isn't really what `wp_is_json_request()` is meant for, but it's the best option until something like
	 * `wp_doing_rest()` is available.
	 *
	 * @link https://core.trac.wordpress.org/ticket/42061
	 */
	return wp_is_json_request() && $query->is_search() && POST_TYPE === $query->get( 'post_type' );
}

/**
 * Customize the ES query for patterns.
 *
 * @see `should_handle_query` has preconditions for this function.
 *
 * @param array    $es_query_args The raw Elasticsearch query args.
 * @param WP_Query $wp_query      The original WP_Query object.
 *
 * @return array
 */
function modify_es_query_args( $es_query_args, $wp_query ) {
	$user_query = $wp_query->get( 's' );
	$locales    = array_unique( $wp_query->get( 'meta_query' )['orderby_locale']['value'] );

	jetpack_require_lib( 'jetpack-wpes-query-builder/jetpack-wpes-query-parser' );

	$parser = new Jetpack_WPES_Search_Query_Parser( $wp_query, array() );

	$must_query = [
		'multi_match' => [
			'query'    => $user_query,
			'fields'   => [ 'title_en', 'meta.wpop_description.value' ],
			'boost'    => 0.1,
			'operator' => 'and',
		],
	];

	$should_query = [
		[
			'multi_match' => [
				'query'  => $user_query,
				'fields' => [ 'title_en' ],
				'boost'  => 2,
				'type'   => 'phrase',
			],
		],

		[
			'multi_match' => [
				// The `description_en` field in the ES index is actually `post_content`, but that's not
				// relevant in this context, since that's just sample content. The `wpop_description`
				// field is the actual description that should be searched.
				'fields' => [ 'meta.wpop_description.value' ],
				'query'  => $user_query,
				'type'   => 'phrase',
			],
		],
	];

	// Requests for a specific locale will still include `en_US` as a fallback.
	if ( count( $locales ) > 1 ) {
		$primary_locale = array_reduce( $locales, function( $carry, $item ) {
			// This assumes there will only be 2 items in $locale.
			if ( 'en_US' !== $item ) {
				$carry = $item;
			}

			return $carry;
		} );

		// Boost the primary locale over the `en_US` fallback.
		$should_query[] = [
			'term' => [
				'meta.wpop_locale.value.raw' => $primary_locale,
				'boost'                      => 2,
			],
		];

		$should_query[] = [
			'term' => [
				'meta.wpop_locale.value.raw' => 'en_US',
				'boost'                      => 0.00001,
				// todo ^ isn't working. might not need the positive boost on $primary_locale once this works.
			],
		];
	}

	$filter = [
		'bool' => [
			'must' => [
				[ 'term' => [ 'post_type' => 'wporg-pattern' ] ],
				[ 'term' => [ 'taxonomy.wporg-pattern-keyword.slug' => 'core' ] ],
				[ 'terms' => [ 'meta.wpop_locale.value.raw' => $locales ] ],
			],
		],
	];

	$parser->add_query( $must_query, 'must' );
	$parser->add_query( $should_query, 'should' );
	$parser->add_filter( $filter );

	$es_query_args['query']  = $parser->build_query();
	$es_query_args['filter'] = $parser->build_filter();

	$es_query_args['sort'] = [
		[
			'_score' => [
				'order' => 'desc',
			],
		],
	];

	return $es_query_args;
}

/**
 * Log when Jetpack does not run the query.
 *
 * @param string $reason
 * @param array  $data
 */
function log_aborted_queries( $reason, $data ) {
	if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
		wp_send_json_error( array( 'jetpack_search_abort - ' . $reason, $data ) );
	} else {
		error_log( 'jetpack_search_abort - cc @iandunn, @tellyworth, @dd32 - ' . $reason . ' - ' . wp_json_encode( $data ), E_USER_ERROR );
	}
}

/**
 * Log when Jetpack gets an error running the query.
 *
 * This filter doesn't currently work, but should in the future.
 * See https://github.com/Automattic/jetpack/issues/18888
 *
 * @param array $data
 */
function log_failed_queries( $data ) {
	if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
		wp_send_json_error( array( 'failed_jetpack_search_query', $data ) );
	} else {
		error_log( 'failed_jetpack_search_query - cc @iandunn, @tellyworth, @dd32 - ' . wp_json_encode( $data ), E_USER_ERROR );
	}
}
