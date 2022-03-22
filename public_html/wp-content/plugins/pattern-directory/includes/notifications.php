<?php

namespace WordPressdotorg\Pattern_Directory\Notifications;

use function WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\get_default_reason_description;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE as PATTERN, UNLISTED_STATUS, SPAM_STATUS };
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ POST_TYPE as FLAG, TAX_TYPE as REASON, PENDING_STATUS };

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_action( 'transition_post_status', __NAMESPACE__ . '\monitor_post_status_transitions', 20, 3 );

/**
 * Hook into post status transitions to detect changes that warrant a notification.
 *
 * @param string   $new_status
 * @param string   $old_status
 * @param \WP_Post $post
 *
 * @return void
 */
function monitor_post_status_transitions( $new_status, $old_status, $post ) {
	if ( PATTERN !== get_post_type( $post ) ) {
		return;
	}

	if ( 'publish' === $new_status && in_array( $old_status, array( 'pending', SPAM_STATUS ) ) ) {
		notify_pattern_approved( $post );
	} elseif (
		SPAM_STATUS === $new_status
		|| ( 'pending' === $new_status && 'publish' === $old_status )
	) {
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
		esc_html( html_entity_decode( $pattern_title ) ),
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

	$flags = get_posts( array(
		'post_type' => FLAG,
		'post_parent' => $post->ID,
		'post_status' => PENDING_STATUS,
	) );
	$reason = '';
	if ( ! empty( $flags ) ) {
		$reasons = array();
		foreach ( $flags as $flag ) {
			$terms = get_the_terms( $flag, REASON );
			if ( is_array( $terms ) ) {
				$reasons += $terms;
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
	} else {
		// If it doesn't have flags, it must have gotten here by getting marked as spam.
		$spam_term = get_term_by( 'slug', '4-spam' );
		$reason = wp_strip_all_tags( $spam_term->description );
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
		esc_html( html_entity_decode( $pattern_title ) ),
		esc_html( html_entity_decode( $reason ) )
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

	$reasons = get_the_terms( $post, REASON );
	$reason = '';
	if ( ! empty( $reasons ) ) {
		$reason_term = reset( $reasons );
		$reason = wp_strip_all_tags( $reason_term->description );
	}

	if ( ! $reason ) {
		$reason = get_default_reason_description();
	}

	if ( $locale ) {
		switch_to_locale( $locale );
	}

	$subject = esc_html__( 'Pattern unlisted', 'wporg-patterns' );

	$message = sprintf(
		// translators: Plaintext email message. Note the line breaks. 1. Pattern title; 2. Pattern URL;
		esc_html__( 'Hello,

Your pattern, %1$s, has been unlisted from the Block Pattern Directory due to the following reason:

%2$s

If you would like to resubmit your pattern, please make sure it follows the guidelines:

%3$s', 'wporg-patterns' ),
		esc_html( html_entity_decode( $pattern_title ) ),
		esc_html( html_entity_decode( $reason ) ),
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
	$message = html_entity_decode( $message );

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



