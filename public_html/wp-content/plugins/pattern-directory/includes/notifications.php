<?php
/**
 * Notifications for the Pattern Directory.
 *
 * @package WordPressdotorg\Pattern_Directory
 */

namespace WordPressdotorg\Pattern_Directory\Notifications;

use function WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\get_default_reason_description;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE as PATTERN, UNLISTED_STATUS, SPAM_STATUS };
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ POST_TYPE as FLAG, TAX_TYPE as REASON, PENDING_STATUS };

defined( 'WPINC' ) || die();

/**
 * The post meta key holding the moderator's message to the author, which is
 * included in the "pattern unlisted" email.
 */
const UNLISTED_DETAIL_META = '_wporg_unlist_reason_detail';

/**
 * Actions and filters.
 */
add_action( 'wp_after_insert_post', __NAMESPACE__ . '\trigger_notifications', 20, 4 );
add_action( 'init', __NAMESPACE__ . '\register_unlisted_meta' );

/**
 * Register the post meta used to store the moderator's message to the author.
 *
 * Only moderators can write it, from the Unlist modal in the block editor. It is
 * read in the edit context only, so the public API never exposes it, and it is
 * deleted as soon as the pattern is unlisted.
 *
 * @return void
 */
function register_unlisted_meta() {
	register_post_meta(
		PATTERN,
		UNLISTED_DETAIL_META,
		array(
			'type'              => 'string',
			'description'       => 'A message from the moderator, included in the email sent to the author when a pattern is unlisted.',
			'single'            => true,
			'show_in_rest'      => array( 'schema' => array( 'context' => array( 'edit' ) ) ),
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => function () {
				return current_user_can( get_post_type_object( PATTERN )->cap->edit_others_posts );
			},
		)
	);
}

/**
 * Fire off relevant notification when a post is finished updating.
 *
 * @param int           $post_id     Post ID.
 * @param \WP_Post      $post        Post object.
 * @param bool          $update      Whether this is an existing post being updated.
 * @param null|\WP_Post $post_before Null for new posts, the WP_Post object prior
 *                                  to the update for updated posts.
 *
 * @return void
 */
function trigger_notifications( $post_id, $post, $update, $post_before ) {
	if ( PATTERN !== get_post_type( $post ) ) {
		return;
	}

	// A missing locale identifies an English original.
	$locale = get_post_meta( $post_id, 'wpop_locale', true );
	if ( $locale && 'en_US' !== $locale ) {
		return;
	}

	if ( ! $update || is_null( $post_before ) ) {
		return;
	}

	$new_status = $post->post_status;
	$old_status = $post_before->post_status;
	if ( $new_status === $old_status ) {
		return;
	}

	if ( 'publish' === $new_status && in_array( $old_status, array( 'pending', SPAM_STATUS, UNLISTED_STATUS ), true ) ) {
		notify_pattern_approved( $post );
	} elseif ( SPAM_STATUS === $new_status ) {
		notify_pattern_flagged( $post );
	} elseif ( UNLISTED_STATUS === $new_status ) {
		notify_pattern_unlisted( $post );
	}
}

/**
 * Notify when a pattern has been approved.
 *
 * @param \WP_Post $post Post being processed.
 *
 * @return void
 */
function notify_pattern_approved( $post ) {
	$author = get_user_by( 'id', $post->post_author );
	if ( ! $author ) {
		return;
	}

	$email  = $author->user_email;
	$locale = get_user_locale( $author );

	$pattern_title = get_the_title( $post );
	$pattern_url   = get_permalink( $post );

	if ( $locale ) {
		switch_to_locale( $locale );
	}

	$subject = esc_html__( 'Pattern published', 'wporg-patterns' );

	$message = sprintf(
		// translators: Plaintext email message. Note the line breaks. 1. Pattern title; 2. Pattern URL.
		esc_html__(
			'Hello!

Thank you for submitting your pattern, %1$s. It is now live in the Block Pattern Directory!

%2$s',
			'wporg-patterns'
		),
		esc_html( $pattern_title ),
		esc_url_raw( $pattern_url )
	);

	if ( $locale ) {
		restore_current_locale();
	}

	send_email( $email, $subject, $message );
}

/**
 * Notify when a pattern has been unpublished for review.
 *
 * Sent on the review status transition for both spam detection and user reports.
 *
 * @param \WP_Post $post Post being processed.
 */
function notify_pattern_flagged( $post ) {
	$author = get_user_by( 'id', $post->post_author );
	if ( ! $author ) {
		return;
	}

	$email  = $author->user_email;
	$locale = get_user_locale( $author );

	$pattern_title = get_the_title( $post );

	if ( $locale ) {
		switch_to_locale( $locale );
	}

	$reason = '';

	// Reports carry their own reasons; the spam term covers the removals that leave no flags behind.
	$flags = get_posts(
		array(
			'post_type'   => FLAG,
			'post_parent' => $post->ID,
			'post_status' => PENDING_STATUS,
		)
	);

	if ( ! empty( $flags ) ) {
		$reasons = array();
		foreach ( $flags as $flag ) {
			$terms = get_the_terms( $flag, REASON );
			if ( is_array( $terms ) ) {
				$reasons = array_merge( $reasons, $terms );
			}
		}
		$reasons = array_map(
			function ( \WP_Term $reason ) {
				return wp_strip_all_tags( $reason->description );
			},
			$reasons
		);
		$reasons = array_unique( $reasons );
		$reason  = trim( implode( "\n", $reasons ) );
	} elseif ( SPAM_STATUS === $post->post_status ) {
		$spam_term = get_term_by( 'slug', '4-spam', REASON );
		$reason    = $spam_term ? wp_strip_all_tags( $spam_term->description ) : '';
	}

	if ( ! $reason ) {
		$reason = get_default_reason_description();
	}

	$subject = esc_html__( 'Pattern being reviewed', 'wporg-patterns' );

	$message = sprintf(
		// translators: Plaintext email message. Note the line breaks. 1. Pattern title; 2. Flag reason(s).
		esc_html__(
			'Hi there!

Thanks for submitting your pattern. Unfortunately, your pattern, %1$s, has been flagged for review due to the following reason(s):

%2$s

Your pattern has been unpublished from the Block Pattern Directory at this time, and will receive further review. If the pattern meets the guidelines, we will re-publish it to the Block Pattern Directory. Thanks for your patience with us volunteer reviewers!',
			'wporg-patterns'
		),
		esc_html( $pattern_title ),
		esc_html( $reason )
	);

	if ( $locale ) {
		restore_current_locale();
	}

	send_email( $email, $subject, $message );
}

/**
 * Notify when a pattern has been unlisted.
 *
 * @param \WP_Post $post Post being processed.
 *
 * @return void
 */
function notify_pattern_unlisted( $post ) {
	// The message is single-use: consume it before anything can bail out, so a
	// later unlisting doesn't resend it.
	$detail = get_post_meta( $post->ID, UNLISTED_DETAIL_META, true );
	delete_post_meta( $post->ID, UNLISTED_DETAIL_META );

	$author = get_user_by( 'id', $post->post_author );
	if ( ! $author ) {
		return;
	}

	$email  = $author->user_email;
	$locale = get_user_locale( $author );

	$pattern_title = get_the_title( $post );

	if ( $locale ) {
		switch_to_locale( $locale );
	}

	$reasons = get_the_terms( $post, REASON );
	$reason  = '';
	if ( ! empty( $reasons ) ) {
		$reason_term = reset( $reasons );
		$reason      = wp_strip_all_tags( $reason_term->description );
	}

	if ( ! $reason ) {
		$reason = get_default_reason_description();
	}

	// Append the moderator's message to the author, if one was provided.
	if ( $detail ) {
		$reason .= "\n\n" . $detail;
	}

	$subject = esc_html__( 'Pattern unlisted', 'wporg-patterns' );

	$message = sprintf(
		// translators: Plaintext email message. Note the line breaks. 1. Pattern title; 2. Unlisting reason; 3. Guidelines URL.
		esc_html__(
			'Hello,

Your pattern, %1$s, has been unlisted from the Block Pattern Directory due to the following reason:

%2$s

If you would like to resubmit your pattern, please make sure it follows the guidelines:

%3$s',
			'wporg-patterns'
		),
		esc_html( $pattern_title ),
		esc_html( $reason ),
		'https://wordpress.org/patterns/about/'
	);

	if ( $locale ) {
		restore_current_locale();
	}

	send_email( $email, $subject, $message );
}

/**
 * Wrapper for wp_mail.
 *
 * @param string $to      Recipient email address.
 * @param string $subject Email subject.
 * @param string $message Email body.
 *
 * @return void
 */
function send_email( $to, $subject, $message ) {
	$message = html_entity_decode( $message, ENT_QUOTES );

	wp_mail(
		$to,
		$subject,
		$message,
		array(
			'From: WordPress Pattern Directory <noreply@wordpress.org>',
			'Reply-To: <themes@wordpress.org>',
		)
	);
}
