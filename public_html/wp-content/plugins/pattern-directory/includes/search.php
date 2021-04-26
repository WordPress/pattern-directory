<?php

namespace WordPressdotorg\Pattern_Directory\Search;


add_filter( 'jetpack_sync_post_meta_whitelist', __NAMESPACE__ . '\sync_pattern_meta' );


/**
 * Tell Jetpack to sync pattern meta, so it can be indexed by ElasticSearch.
 *
 * @param array $post_meta_safelist
 *
 * @return mixed
 */
function sync_pattern_meta( $post_meta_safelist ) {
	$post_meta_safelist[] = 'wpop_description';
	$post_meta_safelist[] = 'wpop_viewport_width';

	return $post_meta_safelist;
}
