<?php
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * Check block type stats
 *
 * To run in a sandbox, use php directly, ex:
 * php ./bin/block-stats.php
 */

namespace WordPressdotorg\Pattern_Directory;

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE };

// This script should only be called in a CLI environment.
if ( 'cli' != php_sapi_name() ) {
	die();
}

$opts = getopt( '', array( 'url:', 'abspath:', 'post_status:', 'verbose' ) );

if ( empty( $opts['url'] ) ) {
	$opts['url'] = 'https://wordpress.org/patterns/';
}

if ( empty( $opts['abspath'] ) && false !== strpos( __DIR__, 'wp-content' ) ) {
	$opts['abspath'] = substr( __DIR__, 0, strpos( __DIR__, 'wp-content' ) );
}

$opts['post_status'] = isset( $opts['post_status'] ) ? explode( ',', $opts['post_status'] ) : array( 'publish' );
$opts['verbose']     = isset( $opts['verbose'] );

// Bootstrap WordPress
$_SERVER['HTTP_HOST']   = parse_url( $opts['url'], PHP_URL_HOST );
$_SERVER['REQUEST_URI'] = parse_url( $opts['url'], PHP_URL_PATH );

require rtrim( $opts['abspath'], '/' ) . '/wp-load.php';

$args = array(
	'post_type' => POST_TYPE,
	'post_status' => $opts['post_status'],
	'posts_per_page' => -1,
	'post_parent' => 0,
	'orderby' => 'date',
	'order' => 'DESC',
);

$query = new \WP_Query( $args );

$type_counts = array();
$total_counts = array();
$lt3_count = 0;
$gt75_count = 0;
while ( $query->have_posts() ) {
	$query->the_post();
	$pattern = get_post();

	$all_blocks = array();
	$blocks = parse_blocks( $pattern->post_content );
	$blocks_queue = $blocks;

	while ( count( $blocks_queue ) > 0 ) { // phpcs:ignore -- inline count OK.
		$block = array_shift( $blocks_queue );
		array_push( $all_blocks, $block );
		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				array_push( $blocks_queue, $inner_block );
			}
		}
	}

	$block_types_count = count( array_unique( wp_list_pluck( $all_blocks, 'blockName' ) ) );
	$block_total_count = count( $all_blocks );

	if ( $block_types_count < 3 ) {
		$lt3_count++;
		if ( $opts['verbose'] ) {
			if ( 1 === $block_types_count ) {
				echo "Pattern has only 1 block type, $block_total_count block(s).\n";
			} else {
				echo "Pattern has $block_types_count block types, $block_total_count blocks.\n";
			}
			echo '  ' . get_permalink() . "\n";
		}
	}

	if ( $block_total_count > 75 ) {
		$gt75_count++;
		if ( $opts['verbose'] ) {
			echo "Pattern has over 75 blocks.\n";
			echo '  ' . get_permalink() . "\n";
		}
	}

	$type_counts[] = $block_types_count;
	$total_counts[] = $block_total_count;
}

echo 'Scanned ' . count( $total_counts ) . " patterns.\n";

echo "$lt3_count patterns have <3 blocks.\n";
echo "$gt75_count patterns have >75 blocks.\n";

$type_average = array_sum( $type_counts ) / count( $type_counts );
$type_min = min( $type_counts );
$type_max = max( $type_counts );
printf(
	"There are %.1f block types per pattern on average, %d min, and %d max.\n",
	$type_average,
	$type_min,
	$type_max
);

$total_average = array_sum( $total_counts ) / count( $total_counts );
$total_min = min( $total_counts );
$total_max = max( $total_counts );
printf(
	"There are %.1f blocks per pattern on average, %d min, and %d max.\n",
	$total_average,
	$total_min,
	$total_max
);
