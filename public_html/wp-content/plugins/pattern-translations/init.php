<?php

namespace A8C\Lib\Patterns;

/**
 * Init function to group together taxonomy registration and any other tasks
 * that need to be performed before patterns are queried.
 */
function patterns_lib_init() {
	if ( ! has_blog_sticker( 'block-patterns-source-site' ) ) {
		return;
	}
	register_pattern_meta_taxonomy();
	register_automatic_pattern_meta_insertion_hooks();
	register_pattern_media_hooks();
	add_filter( 'map_meta_cap', __NAMESPACE__ . '\prevent_delete_attachments', 10, 4 );
}

/**
 * Unload taxonomy registration and hooks loaded by the init function.
 * In most cases this will not need to be called, but it is used when tearing down tests.
 *
 * @see \A8C\Lib\Patterns\patterns_lib_init
 */
function patterns_lib_unload() {
	if ( ! has_blog_sticker( 'block-patterns-source-site' ) ) {
		return;
	}
	unregister_pattern_meta_taxonomy();
	unregister_automatic_pattern_meta_insertion_hooks();
	unregister_pattern_media_hooks();
	remove_filter( 'map_meta_cap', __NAMESPACE__ . '\prevent_delete_attachments', 10 );
}

/**
 * Prevent media (attachments) from being deleted from source sites.
 *
 * Once an image has been uploaded to a source site and then inserted into a user's site
 * via a pattern or page layout, we need to keep that image around or we risk that
 * image 404ing on the user's site. This function prevents us from accidentally
 * deleting media from a valid source site.
 *
 * @param array  $caps    Array of the user's capabilities.
 * @param string $cap     Capability name.
 * @param int    $user_id The user ID.
 * @param array  $args    Adds the context to the cap. Typically the object ID.
 * @return array Primitive caps.
 */
function prevent_delete_attachments( $caps, $cap, $user_id, $args ) {
	if ( 'delete_post' === $cap && 'attachment' === get_post_type( $args[0] ) ) {
		$caps[] = 'do_not_allow';
	}
	return $caps;
}
