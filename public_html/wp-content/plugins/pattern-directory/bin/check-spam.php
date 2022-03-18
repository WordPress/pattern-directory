<?php

namespace WordPressdotorg\Pattern_Directory;

use function WordPressdotorg\Pattern_Directory\Pattern_Validation\check_for_spam;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, SPAM_STATUS };

// This script should only be called in a CLI environment.
if ( 'cli' != php_sapi_name() ) {
	die();
}

$opts = getopt( '', array( 'post:', 'url:', 'abspath:', 'post_status:', 'per_page:', 'all', 'apply', 'verbose' ) );

if ( empty( $opts['url'] ) ) {
	$opts['url'] = 'https://wordpress.org/patterns/';
}

if ( empty( $opts['abspath'] ) && false !== strpos( __DIR__, 'wp-content' ) ) {
	$opts['abspath'] = substr( __DIR__, 0, strpos( __DIR__, 'wp-content' ) );
}

$opts['post_status'] = isset( $opts['post_status'] ) ? explode( ',', $opts['post_status'] ) : array( 'pending' );
$opts['apply']       = isset( $opts['apply'] );
$opts['verbose']     = isset( $opts['verbose'] );
$opts['all']         = isset( $opts['all'] );

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
	'post_type' => POST_TYPE,
	'post_status' => $opts['post_status'],
	'posts_per_page' => $opts['per_page'] ?: -1,
	'post_parent' => 0,
	'orderby' => 'date',
	'order' => 'DESC',
);
if ( isset( $opts['post'] ) ) {
	$args = array(
		'post_type' => POST_TYPE,
		'p' => absint( $opts['post'] ),
	);
}

$query = new \WP_Query( $args );

$count_checked = 0;
$count_spam = 0;
while ( $query->have_posts() ) {
	$count_checked++;
	$query->the_post();
	$pattern = get_post();

	list( $is_spam, $spam_reason ) = check_for_spam(
		array(
			'ID'          => $pattern->ID,
			'post_name'   => $pattern->post_name,
			'post_author' => $pattern->post_author,
			'title'       => $pattern->post_title,
			'content'     => $pattern->post_content,
			'description' => $pattern->wpop_description ?: '',
			'keywords'    => $pattern->wpop_keywords ?: '',
		)
	);

	if ( $is_spam ) {
		$count_spam++;

		if ( $opts['verbose'] ) {
			echo "{$pattern->ID}: Spam found: $spam_reason\n"; // phpcs:ignore
		}

		if ( $opts['apply'] ) {
			wp_update_post(
				array(
					'ID' => $pattern->ID,
					'post_status' => SPAM_STATUS,
				)
			);
			echo "{$pattern->ID}: Post status updated.\n"; // phpcs:ignore

			// Add a note explaining why this post is in pending, if it's due to spam.
			if ( function_exists( '\WordPressdotorg\InternalNotes\create_note' ) ) {
				\WordPressdotorg\InternalNotes\create_note(
					$pattern->ID,
					array(
						'post_author'  => get_user_by( 'login', 'wordpressdotorg' )->ID ?? 0,
						'post_excerpt' => $spam_reason,
					)
				);
			}
		}
	} else {
		if ( $opts['verbose'] ) {
			echo "{$pattern->ID}: Not spam.\n"; // phpcs:ignore
		}
	}
}

echo "$count_checked patterns checked, $count_spam found to be spam.\n"; // phpcs:ignore
