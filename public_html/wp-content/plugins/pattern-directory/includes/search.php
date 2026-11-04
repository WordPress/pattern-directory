<?php
/**
 * Search for the Pattern Directory.
 *
 * @package WordPressdotorg\Pattern_Directory
 */

namespace WordPressdotorg\Pattern_Directory\Search;

use WP_Query;
use Automattic\Jetpack\Search\Helper as Jetpack_Search_Helper;
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
 * @param array $post_meta_safelist Metadata keys allowed in the search index.
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
 * @param bool     $handle_query Whether Jetpack should handle the query.
 * @param WP_Query $query        Query being filtered.
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
	$locales    = array( 'en_US' );

	if ( ! empty( $meta_query['orderby_locale']['value'] ) ) {
		$locales = array_unique( $meta_query['orderby_locale']['value'] );
	}

	$parser = new Jetpack_WPES_Search_Query_Parser( $wp_query, array() );

	$must_query = array(
		'multi_match' => array(
			'query'    => $user_query,
			'fields'   => array( 'title_en', 'meta.wpop_description.value' ),
			'boost'    => 0.1,
			'operator' => 'and',
		),
	);

	$should_query = array(
		array(
			'multi_match' => array(
				'query'  => $user_query,
				'fields' => array( 'title_en' ),
				'boost'  => 2,
				'type'   => 'phrase',
			),
		),

		array(
			'multi_match' => array(
				// The `description_en` field in the ES index is actually `post_content`, but that's not
				// relevant in this context, since that's just sample content. The `wpop_description`
				// field is the actual description that should be searched.
				'fields' => array( 'meta.wpop_description.value' ),
				'query'  => $user_query,
				'type'   => 'phrase',
			),
		),
	);

	// Requests for a specific locale will still include `en_US` as a fallback.
	if ( count( $locales ) > 1 ) {
		$primary_locale = array_reduce(
			$locales,
			function ( $carry, $item ) {
				// This assumes there will only be 2 items in $locale.
				if ( 'en_US' !== $item ) {
					$carry = $item;
				}

				return $carry;
			}
		);

		// Boost the primary locale over the `en_US` fallback.
		$should_query[] = array(
			'boosting' => array(
				'positive'       => array(
					'term' => array(
						'meta.wpop_locale.value.raw' => $primary_locale,
					),
				),
				'negative'       => array(
					'term' => array(
						'meta.wpop_locale.value.raw' => 'en_US',
					),
				),
				'negative_boost' => 0.001,
			),
		);
	}

	$filter = array(
		'bool' => array(
			'must' => array(
				array( 'term' => array( 'post_type' => 'wporg-pattern' ) ),
				array( 'terms' => array( 'meta.wpop_locale.value.raw' => $locales ) ),
			),
		),
	);

	$tax_filter = get_tax_filter( $wp_query->get( 'tax_query' ) );
	if ( $tax_filter ) {
		$filter['bool']['must'][] = $tax_filter;
	}

	// This replaces the filter Jetpack built, so carry over what it covered and add what it doesn't know about.
	$filter['bool']['must'] = array_merge( $filter['bool']['must'], get_query_filters( $wp_query->query_vars ) );

	$parser->add_query( $must_query, 'must' );
	$parser->add_query( $should_query, 'should' );
	$parser->add_filter( $filter );

	$es_query_args['query']  = $parser->build_query();
	$es_query_args['filter'] = $parser->build_filter();

	// Jetpack only pages; `from` is the ES offset, capped as Jetpack caps it.
	if ( $wp_query->get( 'offset' ) ) {
		$es_query_args['from'] = min( absint( $wp_query->get( 'offset' ) ), Jetpack_Search_Helper::get_max_offset() );
	}

	// Jetpack already sorted by `orderby`, and the REST API asks for `relevance` unless the request picks an order.
	if ( empty( $es_query_args['sort'] ) ) {
		$es_query_args['sort'] = array(
			array(
				'_score' => array(
					'order' => 'desc',
				),
			),
		);
	}

	return $es_query_args;
}

/**
 * Convert the query's author, ID and allowed blocks restrictions into Elasticsearch filter clauses.
 *
 * The MySQL query applies these through `author`, `post__in` and the `allowed_blocks` meta query, which an ES
 * search otherwise ignores.
 *
 * @param array $query_vars The query's vars.
 *
 * @return array[] Filter clauses, all of which must match.
 */
function get_query_filters( $query_vars ) {
	$clauses = array();

	// `post__in` is also how the REST filters say "match nothing", with an ID of 0 or -1.
	if ( ! empty( $query_vars['post__in'] ) ) {
		$clauses[] = array( 'terms' => array( 'post_id' => array_map( 'intval', (array) $query_vars['post__in'] ) ) );
	}

	/*
	 * `author_name` resolves to `author`, the REST `author` param to `author__in`. The index stores logins, not IDs.
	 * A negative `author` excludes, which no search request sends.
	 */
	$author_ids = array_merge(
		wp_parse_id_list( $query_vars['author__in'] ?? array() ),
		array_filter(
			array_map( 'intval', wp_parse_list( $query_vars['author'] ?? '' ) ),
			function ( $author_id ) {
				return $author_id > 0;
			}
		)
	);
	if ( $author_ids ) {
		$logins    = wp_list_pluck(
			get_users(
				array(
					'include' => $author_ids,
					'fields'  => array( 'user_login' ),
				)
			),
			'user_login'
		);
		$clauses[] = $logins
			? array( 'terms' => array( 'author_login' => array_values( $logins ) ) )
			: array( 'terms' => array( 'post_id' => array( 0 ) ) );
	}

	// Lucene regular expressions are always anchored and treat `^` and `$` as literals, so drop the MySQL anchors.
	$allowed_blocks = $query_vars['meta_query']['allowed_blocks']['value'] ?? '';
	if ( $allowed_blocks ) {
		$clauses[] = array( 'regexp' => array( 'meta.wpop_contains_block_types.value.raw' => trim( $allowed_blocks, '^$' ) ) );
	}

	return $clauses;
}

/**
 * Convert a WP_Query `tax_query` into an Elasticsearch filter.
 *
 * Covers the clauses the REST API builds: term IDs from `pattern-categories`/`pattern-keywords` (with their
 * `_exclude` and `operator: AND` forms, and `tax_relation`), and the `core` keyword slug from `curation`.
 * Clauses on other taxonomies or with other operators are ignored.
 *
 * @param mixed $tax_query The query's `tax_query` var.
 *
 * @return array|null The filter, or null when nothing applies.
 */
function get_tax_filter( $tax_query ) {
	if ( ! is_array( $tax_query ) ) {
		return null;
	}

	$clauses = array();

	foreach ( $tax_query as $clause ) {
		// Skips the `relation` key, which is a string.
		if ( ! is_array( $clause ) || ! isset( $clause['taxonomy'], $clause['terms'] ) ) {
			continue;
		}

		$taxonomy = $clause['taxonomy'];

		// `wporg-pattern-flag-reason` is private.
		if ( ! in_array( $taxonomy, array( 'wporg-pattern-category', 'wporg-pattern-keyword' ), true ) ) {
			continue;
		}

		// WP_Query accepts a single term as a string, but the ES `terms` query rejects anything but an array.
		$terms = (array) $clause['terms'];

		// The index is filtered by term ID, so resolve slugs, which `curation` passes.
		if ( isset( $clause['field'] ) && 'slug' === $clause['field'] ) {
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'slug'       => $terms,
					'fields'     => 'ids',
					'hide_empty' => false,
				)
			);
			$terms = is_wp_error( $terms ) ? array() : $terms;
		}

		$terms    = array_values( wp_parse_id_list( $terms ) );
		$field    = "taxonomy.$taxonomy.term_id";
		$operator = strtoupper( $clause['operator'] ?? 'IN' );

		if ( 'IN' === $operator ) {
			$clauses[] = array( 'terms' => array( $field => $terms ) );
		} elseif ( 'NOT IN' === $operator ) {
			$clauses[] = array(
				'bool' => array(
					'must_not' => array( array( 'terms' => array( $field => $terms ) ) ),
				),
			);
		} elseif ( 'AND' === $operator && $terms ) {
			$clauses[] = array(
				'bool' => array(
					'must' => array_map(
						function ( $term_id ) use ( $field ) {
							return array( 'term' => array( $field => $term_id ) );
						},
						$terms
					),
				),
			);
		}
	}

	if ( ! $clauses ) {
		return null;
	}

	if ( isset( $tax_query['relation'] ) && 'OR' === strtoupper( $tax_query['relation'] ) ) {
		return array(
			'bool' => array(
				'should'               => $clauses,
				'minimum_should_match' => 1,
			),
		);
	}

	return array( 'bool' => array( 'must' => $clauses ) );
}

/**
 * Log when Jetpack does not run the query.
 *
 * @param string $reason Reason the search was aborted.
 * @param array  $data   Data to process.
 */
function log_aborted_queries( $reason, $data ) {
	if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
		wp_send_json_error( array( 'jetpack_search_abort - ' . $reason, $data ) );
	} else {
		trigger_error( 'jetpack_search_abort - ' . $reason . ' - ' . wp_json_encode( $data ), E_USER_WARNING ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Forward search failures to the configured PHP error handler.
	}
}

/**
 * Log when Jetpack gets an error running the query.
 *
 * This filter doesn't currently work, but should in the future.
 * See https://github.com/Automattic/jetpack/issues/18888
 *
 * @param array $data Data to process.
 */
function log_failed_queries( $data ) {
	if ( defined( 'WPORG_SANDBOXED' ) && WPORG_SANDBOXED ) {
		wp_send_json_error( array( 'failed_jetpack_search_query', $data ) );
	} else {
		trigger_error( 'failed_jetpack_search_query - ' . wp_json_encode( $data ), E_USER_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error -- Forward search failures to the configured PHP error handler.
	}
}
