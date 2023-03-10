<?php

namespace WordPressdotorg\Pattern_Directory\Search;

use WP_Query;
use Automattic\Jetpack\Search\WPES\Query_Parser as Jetpack_WPES_Search_Query_Parser;
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
	$post_meta_safelist[] = 'wpop_keywords';
	$post_meta_safelist[] = 'wpop_viewport_width';
	$post_meta_safelist[] = 'wpop_locale';
	$post_meta_safelist[] = 'wpop_contains_block_types';

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
	return defined( 'REST_REQUEST' ) && REST_REQUEST && $query->is_search() && POST_TYPE === $query->get( 'post_type' );
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
	$meta_query = $wp_query->get( 'meta_query' );
	$locales    = [ 'en_US' ];

	if ( ! empty( $meta_query['orderby_locale']['value'] ) ) {
		$locales = array_unique( $meta_query['orderby_locale']['value'] );
	}

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
			'boosting' => [
				'positive' => [
					'term' => [
						'meta.wpop_locale.value.raw' => $primary_locale,
					],
				],
				'negative' => [
					'term' => [
						'meta.wpop_locale.value.raw' => 'en_US',
					],
				],
				'negative_boost' => 0.001,
			],
		];
	}

	$filter = [
		'bool' => [
			'must' => [
				[ 'term' => [ 'post_type' => 'wporg-pattern' ] ],
				[ 'terms' => [ 'meta.wpop_locale.value.raw' => $locales ] ],
			],
		],
	];

	$tax_query = $wp_query->get( 'tax_query' );
	if ( $tax_query ) {
		foreach ( $tax_query as $term ) {
			$taxonomy = $term['taxonomy'];

			// `wporg-pattern-flag-reason` is private.
			if ( ! in_array( $taxonomy, array( 'wporg-pattern-category', 'wporg-pattern-keyword' ) ) ) {
				continue;
			}

			$filter['bool']['must'][] = [
				'terms' => [ "taxonomy.$taxonomy.term_id" => $term['terms'] ],
			];
		}
	}

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
		trigger_error( 'jetpack_search_abort - ' . $reason . ' - ' . wp_json_encode( $data ), E_USER_WARNING ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
		trigger_error( 'failed_jetpack_search_query - ' . wp_json_encode( $data ), E_USER_WARNING );
	}
}
