<?php

namespace WordPressdotorg\Pattern_Directory\Search;
use WP_Query;

// enable the search module
add_filter( 'option_jetpack_active_modules', function( $modules ) {
	// probably only do this for pattern searches specifically, maybe on through rest api too
	// maybe have an init() function that only registers all the callbacks if ^ conditions are met

	$modules[] = 'search';

	return array_unique( $modules );
} );

/**
 * @param string $reason Reason for Search fallback.
 * @param mixed  $data   Data associated with the request, such as attempted search parameters.
 */
add_action( 'jetpack_search_abort', function( $reason, $data ) {
	// log to slack

} );
// also call something similar on `failed_jetpack_search_query`

add_filter( 'jetpack_search_should_handle_query', function( $handle_query, $query ) {
	if ( $handle_query ) { // todo - add `|| $query doesn't match pattern directory` condition
		return $handle_query;
	}

	/*
	 * Enable for REST API requests.
	 *
	 * This isn't really what `wp_is_json_request()` is meant for, but it's the best option until
	 * https://core.trac.wordpress.org/ticket/42061 is resolved.
	 *
	 * @todo Replace with `wp_doing_rest()` (or whatever) once that's available.
	 */
	return wp_is_json_request();
}, 10, 2 );
// todo need to commit api.w.org http_accept change for ^ to work in prod


// need to safelist the CPT for syncing?
// https://jetpack.com/support/related-posts/customize-related-posts/#related-posts-custom-post-types
// something else i read made it sound like everything is synced to ES by default, so wait and see if it's needed

/*
 * todo tweak query
 *
 *  "query": {
        "function_score": {
            "query": {
                "bool": {
                    "must": [
                        {
                            "multi_match": {
                                "fields": [
                                       // change these to just the title, description. anything else? maybe category and keywords?
                                ],
                                "query": "button",
                                "operator": "and"
                            }
                        }
                    ],
                    "should": [
                        {
                            "multi_match": {
                                "fields": [
                                    // need to understand diff between this and "must", and type:best_fields, type phrase, etc.
                                ],
                                "query": "button",
                                "operator": "and",
                                "type": "best_fields"
                            }
                        },
                        {
                            "multi_match": {
                                "fields": [
                                    // same as above
                                ],
                                "query": "button",
                                "operator": "and",
                                "type": "phrase"
                            }
                        }
                    ]
                }
            },
 */


/**
 * Modify the search query parameters, such as controlling the post_type.
 *
 * These arguments are in the format of WP_Query arguments
 *
 * @module search
 *
 * @param array    $es_wp_query_args The current query args, in WP_Query format.
 * @param WP_Query $query            The original WP_Query object.
 *
 * @since  5.0.0
 *
 */
add_filter( 'jetpack_search_es_wp_query_args', function( $wp_query_args, $query ) {
	//	print_r( $wp_query_args );

	// do anything that _can_ be done here, b/c less fragile

	// can set query_fields here instead of below?
	// looks like, but will that result in the best query, or is it just for back-compat, and not very efficient?

	return $wp_query_args;
}, 10, 2 );

/**
 * Modify the underlying ES query that is passed to the search endpoint. The returned args must represent a valid
 * ES query
 *
 * This filter is harder to use if you're unfamiliar with ES, but allows complete control over the query
 *
 * @module search
 *
 * @param array    $es_query_args The raw Elasticsearch query args.
 * @param WP_Query $query         The original WP_Query object.
 *
 * @since  5.0.0
 *
 */
add_filter( 'jetpack_search_es_query_args', function( $es_query_args, $query ) {
	//		print_r($es_query_args);

	// limit it to just title and meta.description, etc

	//		die(__FUNCTION__);

	return $es_query_args;
}, 10, 2 );


// maybe use these
// jetpack_search_should_handle_query
// do_search() / search()
// "WP Core doesn't call the set_found_posts and its filters when filtering posts_pre_query like we do, so need to do these manually."
// jetpack_search_es_wp_query_args
// store_query_failure / print_query_failure / store_last_query_info
// get_search_result()
// add_post_type_aggregation_to_es_query_builder, also tax, add_es_filters, etc
// info from https://jetpack.com/support/search/?site=wordpress.org::patterns

// make sure all customizations are limited to just pattern directory searches


// document that search indices were manually created
add_filter( 'pre_option_has_jetpack_search_product', '__return_true' );

/*
 * need this stuff from alex's pr?
 * jetpack_active_modules
 * option_jetpack_active_modules
 * jetpack_search_es_wp_query_args
 * jetpack_search_abort
 * did_jetpack_search_query
 *
 */
