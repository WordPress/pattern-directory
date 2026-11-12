<?php
/**
 * Activity items on WordPress.org profiles for pattern authors.
 *
 * @package WordPressdotorg\Pattern_Directory
 */

namespace WordPressdotorg\Pattern_Directory\Profile_Activity;

use function WordPressdotorg\Profiles\add_activity;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE as PATTERN_POST_TYPE;

/**
 * Post meta that marks a pattern whose publication is already on its author's profile.
 */
const ACTIVITY_SENT_META = '_wpop_profile_activity_sent';

/**
 * Actions and filters.
 */
add_action( 'transition_post_status', __NAMESPACE__ . '\maybe_add_published_activity', 10, 3 );

/**
 * Add an activity item to the author's WordPress.org profile when their pattern is published.
 *
 * Only the first publication counts, so a pattern that is unlisted and published again does not get a second
 * item. Translations are separate posts with the original pattern as their parent and the same author, so they
 * are skipped.
 *
 * @param string   $new_status New post status.
 * @param string   $old_status Previous post status.
 * @param \WP_Post $post       Post being transitioned.
 */
function maybe_add_published_activity( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}

	$post = get_post( $post );

	if ( ! $post || PATTERN_POST_TYPE !== $post->post_type || $post->post_parent ) {
		return;
	}

	if ( ! function_exists( 'WordPressdotorg\Profiles\add_activity' ) ) {
		return;
	}

	if ( get_post_meta( $post->ID, ACTIVITY_SENT_META, true ) ) {
		return;
	}

	$permalink = get_permalink( $post );

	$sent = add_activity(
		'patterns',
		'pattern_create',
		(int) $post->post_author,
		array(
			'primary_link' => $permalink,
			'item_id'      => $post->ID,
			'message'      => sprintf(
				'Published a new pattern, <a href="%s">%s</a>',
				esc_url( $permalink ),
				esc_html( get_the_title( $post ) )
			),
		)
	);

	if ( $sent ) {
		update_post_meta( $post->ID, ACTIVITY_SENT_META, time() );
	}
}
