<?php

namespace WordPressdotorg\Pattern_Directory\Notifications;

use function WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\get_default_reason_description;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE as PATTERN, UNLISTED_STATUS, SPAM_STATUS };
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ POST_TYPE as FLAG, TAX_TYPE as REASON, PENDING_STATUS };

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_action( 'wp_after_insert_post', __NAMESPACE__ . '\trigger_notifications', 20, 4 );
add_action( 'wporg_unlist_pattern', __NAMESPACE__ . '\notify_pattern_flagged' );

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

	// Skip notifications on translated patterns.
	if ( 'en_US' !== get_post_meta( $post_id, 'wpop_locale', true ) ) {
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

	if ( 'publish' === $new_status && in_array( $old_status, array( 'pending', SPAM_STATUS, UNLISTED_STATUS ) ) ) {
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
 * @param \WP_Post $post
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
	$pattern_url = get_permalink( $post );

	if ( $locale ) {
		switch_to_locale( $locale );
	}

	$subject = esc_html__( 'Pattern published', 'wporg-patterns' );

	$message = sprintf(
		// translators: Plaintext email message. Note the line breaks. 1. Pattern title; 2. Pattern URL;
		esc_html__( 'Hello!

Thank you for submitting your pattern, %1$s. It is now live in the Block Pattern Directory!

%2$s', 'wporg-patterns' ),
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
 * This is called either when the status transitions into "spam", or when a post
 * crosses the flag threshold.
 *
 * @param \WP_Post $post
 *
 * @return void
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

	if ( SPAM_STATUS === $post->post_status ) {
		$spam_term = get_term_by( 'slug', '4-spam', REASON );
		$reason = wp_strip_all_tags( $spam_term->description );
	} else {
		$flags = get_posts( array(
			'post_type' => FLAG,
			'post_parent' => $post->ID,
			'post_status' => PENDING_STATUS,
		) );
		if ( ! empty( $flags ) ) {
			$reasons = array();
			foreach ( $flags as $flag ) {
				$terms = get_the_terms( $flag, REASON );
				if ( is_array( $terms ) ) {
					$reasons = array_merge( $reasons, $terms );
				}
			}
			$reasons = array_map(
				function( \WP_Term $reason ) {
					return wp_strip_all_tags( $reason->description );
				},
				$reasons
			);
			$reasons = array_unique( $reasons );
			$reason = trim( implode( "\n", $reasons ) );
		}
	}

	if ( ! $reason ) {
		$reason = get_default_reason_description();
	}

	$subject = esc_html__( 'Pattern being reviewed', 'wporg-patterns' );

	$message = sprintf(
		// translators: Plaintext email message. Note the line breaks. 1. Pattern title; 2. Pattern URL;
		esc_html__( 'Hi there!

Thanks for submitting your pattern. Unfortunately, your pattern, %1$s, has been flagged for review due to the following reason(s):

%2$s

Your pattern has been unpublished from the Block Pattern Directory at this time, and will receive further review. If the pattern meets the guidelines, we will re-publish it to the Block Pattern Directory. Thanks for your patience with us volunteer reviewers!', 'wporg-patterns' ),
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
 * @param \WP_Post $post
 *
 * @return void
 */
function notify_pattern_unlisted( $post ) {
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
	$reason = '';
	if ( ! empty( $reasons ) ) {
		$reason_term = reset( $reasons );
		$reason = wp_strip_all_tags( $reason_term->description );
	}

	if ( ! $reason ) {
		$reason = get_default_reason_description();
	}

	$subject = esc_html__( 'Pattern unlisted', 'wporg-patterns' );

	$message = sprintf(
		// translators: Plaintext email message. Note the line breaks. 1. Pattern title; 2. Pattern URL;
		esc_html__( 'Hello,

Your pattern, %1$s, has been unlisted from the Block Pattern Directory due to the following reason:

%2$s

If you would like to resubmit your pattern, please make sure it follows the guidelines:

%3$s', 'wporg-patterns' ),
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
 * @param string $to
 * @param string $subject
 * @param string $message
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
