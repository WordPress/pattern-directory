<?php
/**
 * Update all patterns with the "contains block types" meta field.
 *
 * To run locally, use `wp-env`, ex:
 * npm run wp-env run cli "php wp-content/plugins/pattern-directory/bin/update-contains-block-types.php --all --per_page=100 --apply"
 *
 * To run in a sandbox, use php directly, ex:
 * php ./bin/update-contains-block-types.php --all --apply
 */

namespace WordPressdotorg\Pattern_Directory;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE };

// This script should only be called in a CLI environment.
if ( 'cli' != php_sapi_name() ) {
	die();
}

$opts = getopt( '', array( 'post:', 'url:', 'abspath:', 'per_page:', 'all', 'apply', 'verbose' ) );

if ( empty( $opts['url'] ) ) {
	$opts['url'] = 'https://wordpress.org/patterns/';
}

if ( empty( $opts['abspath'] ) && false !== strpos( __DIR__, 'wp-content' ) ) {
	$opts['abspath'] = substr( __DIR__, 0, strpos( __DIR__, 'wp-content' ) );
}

$opts['apply']   = isset( $opts['apply'] );
$opts['verbose'] = isset( $opts['verbose'] );
$opts['all']     = isset( $opts['all'] );

// Bootstrap WordPress
$_SERVER['HTTP_HOST']   = parse_url( $opts['url'], PHP_URL_HOST );
$_SERVER['REQUEST_URI'] = parse_url( $opts['url'], PHP_URL_PATH );

require rtrim( $opts['abspath'], '/' ) . '/wp-load.php';

if ( ! $opts['all'] && ! isset( $opts['post'] ) ) {
	fwrite( STDERR, "Error! Either specify a post ID with --post=<ID> or explicitly run over --all.\n" );
	die();
}

if ( ! $opts['apply'] ) {
	echo "Dry run, will not update any patterns.\n";
}

$args = array(
	'post_type'      => POST_TYPE,
	'post_status'    => array( 'publish', 'pending' ),
	'posts_per_page' => isset( $opts['per_page'] ) ? $opts['per_page'] : -1,
	'post_parent'    => 0,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'meta_query'     => array(
		// Only update patterns without this meta.
		array(
			'key'     => 'wpop_contains_block_types',
			'compare' => 'NOT EXISTS',
		),
	),
);
if ( isset( $opts['post'] ) ) {
	$args = array(
		'post_type' => POST_TYPE,
		'p' => absint( $opts['post'] ),
	);
}

$query = new \WP_Query( $args );
$meta_updated = 0;

while ( $query->have_posts() ) {
	$query->the_post();
	$pattern    = get_post();
	$pattern_id = $pattern->ID;
	$blocks     = parse_blocks( $pattern->post_content );
	$all_blocks = _flatten_blocks( $blocks );

	// Get the list of block names and convert it to a single string.
	$block_names = wp_list_pluck( $all_blocks, 'blockName' );
	$block_names = array_filter( $block_names );
	$block_names = array_unique( $block_names );
	sort( $block_names );
	$used_blocks = implode( ',', $block_names );

	if ( $opts['apply'] ) {
		$result = update_post_meta( $pattern_id, 'wpop_contains_block_types', $used_blocks );
		if ( $result ) {
			$meta_updated++;
		} else if ( $opts['verbose'] ) {
			echo "Error updating {$pattern_id}.\n"; // phpcs:ignore
		}
	} else if ( $opts['verbose'] ) {
		echo "Will update {$pattern_id} with '{$used_blocks}'.\n"; // phpcs:ignore
	}
}

echo "Updated {$meta_updated} patterns.\n"; // phpcs:ignore
echo "Done.\n\n"; // phpcs:ignore
