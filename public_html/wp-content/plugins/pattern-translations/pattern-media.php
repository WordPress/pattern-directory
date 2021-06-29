<?php

namespace A8C\Lib\Patterns;

/**
 * Filter the upload size limit.
 *
 * @param  { array } $file An array of file properties.
 * @return { array }       The filtered file array.
 */
function limit_pattern_media_upload_size( $file ) {
	$file_size = $file['size'] ?? null;
	$file_type = $file['type'] ?? null;

	// For unit testing, which runs through `wp_handle_sideload_prefilter`,
	// there is curiously no file information aside from the name and tmp_name keys.
	if ( ! $file_size && $file['tmp_name'] ) {
		$file_size = filesize( $file['tmp_name'] );
	}

	if ( ! $file_type && $file['name'] ) {
		$file_type = 'image/' . pathinfo( $file['name'], PATHINFO_EXTENSION );
	}

	// Limit the check to images supporting by WordPress
	if ( preg_match( '/^image\/(jpeg|png|jpg|gif|ico)$/', $file_type ) ) {
		// Limit to 350kb.
		// This is the maximum file size headstart can handle before complaining.
		// See `pre_check_theme_annotation()` in "wp-content/lib/headstart/class-headstart-generate-annotation.php".
		$file_size_limit_in_kb = 350;
		$current_size_in_kb    = $file_size / 1024; //get size in KB

		if ( $current_size_in_kb > $file_size_limit_in_kb ) {
			$file['error'] = sprintf( 'ERROR: For design starter sites, there is a image file size limit of %d KB. This image is %d KB. Please optimize the image or reduce dimensions.', $file_size_limit_in_kb, $current_size_in_kb );
		}
	}

	return $file;
}

/**
 * Register the callback for automatically managing pattern media.
 */
function register_pattern_media_hooks() {
	if ( ! has_blog_sticker( 'block-patterns-source-site' ) ) {
		return;
	}

	add_filter( 'wp_handle_sideload_prefilter', __NAMESPACE__ . '\limit_pattern_media_upload_size', 10, 1 );
	add_filter( 'wp_handle_upload_prefilter', __NAMESPACE__ . '\limit_pattern_media_upload_size', 10, 1 );
}

/**
 * Unregister the callback for automatically managing pattern media.
 */
function unregister_pattern_media_hooks() {
	if ( ! has_blog_sticker( 'block-patterns-source-site' ) ) {
		return;
	}

	remove_filter( 'wp_handle_sideload_prefilter', __NAMESPACE__ . '\limit_pattern_media_upload_size', 10, 1 );
	remove_filter( 'wp_handle_upload_prefilter', __NAMESPACE__ . '\limit_pattern_media_upload_size', 10, 1 );
}
