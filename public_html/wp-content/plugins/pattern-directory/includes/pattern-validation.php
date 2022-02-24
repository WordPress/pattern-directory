<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Validation;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };

use WordPressdotorg\Pattern_Translations\Pattern as Translations_Pattern;
use WordPressdotorg\Pattern_Translations\PatternParser as Translations_PatternParser;

add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_content', 10, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_title', 11, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_status', 11, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_against_spam', 20, 2 );

/**
 * Strip out basic HTML to get at the manually-entered content in block content.
 *
 * First, remove class attributes, since custom class names will be caught by attribute checks.
 * Next, remove empty alt tags, which are present on default image blocks.
 * Lastly, remove any HTML tags without attributes- this regex catches opening, closing, and self-closing tags.
 * After all this, any block_content left should be there intentionally by the author.
 *
 * @param string $html The block content, from `innerHTML` of a parsed block.
 * @return string Any content that doesn't match the cases described above.
 */
function strip_basic_html( $html ) {
	$to_replace = array( '/class="[^"]*"/', '/alt=""/', '/<\/?[a-zA-Z]+\s*\/?>/' );
	return trim( preg_replace( $to_replace, '', $html ) );
}

/**
 * Check if a block has been edited by the user, as opposed to an empty/placeholder block.
 *
 * @param array $block A parsed block object.
 * @return bool Whether the block has been edited.
 */
function is_not_empty_block( $block ) {
	$registry = \WP_Block_Type_Registry::get_instance();
	$block_type = $registry->get_registered( $block['blockName'] );

	// Paragraphs are a special case, these should never be empty.
	if ( 'core/paragraph' === $block['blockName'] ) {
		$block_content = strip_basic_html( $block['innerHTML'] );
		if ( empty( $block_content ) ) {
			return false;
		}
	}

	// Allow dynamic blocks, which contain no content and maybe no attributes.
	$allowed_empty = [ 'core/archives', 'core/calendar', 'core/latest-posts', 'core/separator', 'core/spacer', 'core/tag-cloud' ];
	if ( in_array( $block['blockName'], $allowed_empty ) ) {
		return true;
	}

	// Check if the attributes are different from the default attributes.
	$block_attrs = $block_type->prepare_attributes_for_render( $block['attrs'] );
	$default_attrs = $block_type->prepare_attributes_for_render( array() );
	if ( $block_attrs != $default_attrs ) {
		return true;
	}

	// If there are any child blocks, check those. Only return if there are real child blocks,
	// otherwise continue on to check for any other content.
	if ( count( $block['innerBlocks'] ) >= 1 ) {
		$child_blocks = array_filter( $block['innerBlocks'], __NAMESPACE__ . '\is_not_empty_block' );
		if ( count( $child_blocks ) ) {
			return true;
		}
	}

	$block_content = strip_basic_html( $block['innerHTML'] );
	if ( ! empty( $block_content ) ) {
		return true;
	}
	return false;
}

/**
 * Validate the pattern content.
 */
function validate_content( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	// If post_content does not exist, this is just an update to an existing pattern.
	if ( ! isset( $prepared_post->post_content ) ) {
		return $prepared_post;
	}

	$content = $prepared_post->post_content;
	if ( ! $content ) {
		return new \WP_Error(
			'rest_pattern_empty',
			__( 'Pattern content cannot be empty.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	// The editor adds in linebreaks between blocks, but parse_blocks thinks those are invalid blocks.
	$content = str_replace( "\n\n", '', $content );
	$blocks = parse_blocks( $content );
	$registry = \WP_Block_Type_Registry::get_instance();

	// $blocks contains a list of the blocks in the content. By default it will always have one item, even if it's
	// not valid block content. Instead, we should check that each block in the list has a blockName.
	$invalid_blocks = array_filter( $blocks, function( $block ) use ( $registry ) {
		$block_type = $registry->get_registered( $block['blockName'] );
		return is_null( $block['blockName'] ) || is_null( $block_type );
	} );
	if ( count( $invalid_blocks ) ) {
		return new \WP_Error(
			'rest_pattern_invalid_blocks',
			__( 'Pattern content contains invalid blocks.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	// Next, we should check that we have at least one non-empty block.
	$real_blocks = array_filter( $blocks, __NAMESPACE__ . '\is_not_empty_block' );

	if ( ! count( $real_blocks ) ) {
		return new \WP_Error(
			'rest_pattern_empty_blocks',
			__( 'Pattern content contains only empty blocks.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	return $prepared_post;
}

/**
 * Validate the pattern title.
 */
function validate_title( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	$status = isset( $request['status'] ) ? $request['status'] : get_post_status( $prepared_post->ID );
	// Bypass this validation for drafts.
	if ( 'draft' === $status || 'auto-draft' === $status ) {
		return $prepared_post;
	}

	// A title exists, but is empty -- invalid.
	if ( isset( $request['title'] ) && empty( trim( $request['title'] ) ) ) {
		return new \WP_Error(
			'rest_pattern_empty_title',
			__( 'A pattern title is required.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	// The existing pattern doesn't have a title, and none is set -- invalid.
	$post_title = get_the_title( $prepared_post->ID );
	if ( empty( $post_title ) && ! isset( $request['title'] ) ) {
		return new \WP_Error(
			'rest_pattern_empty_title',
			__( 'A pattern title is required.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	return $prepared_post;
}

/**
 * Validate the pattern status.
 *
 * Ensures patterns created via the API have either a non-public status (draft, unlisted),
 * or they use the chosen status set in /wp-admin/options-general.php?page=wporg-pattern-creator.
 */
function validate_status( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	$target_status  = isset( $request['status'] ) ? $request['status'] : '';
	$current_status = get_post_status( $prepared_post->ID );

	// Drafts or unlisted patterns are OK.
	if ( in_array( $target_status, [ 'draft', 'auto-draft', UNLISTED_STATUS ] ) ) {
		return $prepared_post;
	}

	// No validation needed if there's no status change.
	if ( $target_status === $current_status || '' === $target_status ) {
		return $prepared_post;
	}

	$default_status = get_option( 'wporg-pattern-default_status', 'publish' );
	$valid_states   = array_unique( array( 'pending', SPAM_STATUS, $default_status ) );

	// Make sure the target status is the expected status (publish or pending).
	if ( ! in_array( $target_status, $valid_states, true ) ) {
		return new \WP_Error(
			'rest_pattern_invalid_status',
			sprintf(
				__( 'Invalid post status. Status must be %s.', 'wporg-patterns' ),
				$default_status
			),
			array( 'status' => 400 )
		);
	}

	// Do not allow for non-privledged users to move a spam post to another status.
	if (
		SPAM_STATUS === $current_status &&
		SPAM_STATUS !== $target_status &&
		! current_user_can( $post_type->cap->edit_others_patterns )
	) {
		return new \WP_Error(
			'rest_pattern_invalid_status',
			sprintf(
				__( 'Invalid post status. Status must be %s.', 'wporg-patterns' ),
				SPAM_STATUS
			),
			array( 'status' => 400 )
		);
	}

	return $prepared_post;
}

/**
 * Validate the pattern doesn't appear to be spam.
 */
function validate_against_spam( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	$target_status = isset( $request['status'] ) ? $request['status'] : '';

	// Only run spam checks at publish time.
	if ( 'publish' !== $target_status ) {
		return $prepared_post;
	}

	// Extract strings and URLs, run against spam checks.
	$post = get_post( $prepared_post->ID );

	$title       = $prepared_post->post_title ?? $post->post_title;
	$content     = $prepared_post->post_content ?? $post->post_content;
	$description = $request['meta']['wpop_description'] ?? ( $post->wpop_description ?: '' );
	$keywords    = $request['meta']['wpop_keywords'] ?? ( $post->wpop_keywords ?: '' );

	// Extract URLs.
	$links = array();
	if ( preg_match_all( '![\b\W"\'](?P<link>((http|https|ftp|mailto):)?//[/.\w]+)[\b\W"\']!', $content, $m ) ) {
		$links = array_unique( $m['link'] );
	}

	// Stringify.
	if ( ! class_exists( '\WordPressdotorg\Pattern_Translations\Pattern' ) ) {
		// This is just a fall-back for local environments where the Translator isn't active.
		// not designed to be used in production.
		$strings = array(
			$title,
			$description,
			wp_strip_all_tags( $content ),
			$keywords,
		);
	} else {
		$pattern              = new Translations_Pattern();
		$pattern->ID          = $post->ID;
		$pattern->title       = $title;
		$pattern->name        = $post->post_name;
		$pattern->description = $description;
		$pattern->keywords    = $keywords;
		$pattern->html        = $content;
		$pattern->locale      = get_locale();

		$parser  = new Translations_PatternParser( $pattern );
		$strings = $parser->to_strings();
	}

	// Combine strings for ease of use.
	$combined_strings = implode( "\n", $strings );
	$combined_links   = implode( "\n", $links );
	$combined         = $combined_strings . "\n" . $combined_links;

	// Not yet detected as spam.
	$is_spam     = false;
	$spam_reason = '';

	// Treat Paragraph-only submissions as likely spam.
	if ( ! $is_spam ) {
		// Only fetches the top-level of blocks, we're only
		$block_names_in_use = array_filter(
			array_unique(
				wp_list_pluck(
					parse_blocks( $content ),
					'blockName'
				)
			)
		);

		if ( array( 'core/paragraph' ) === $block_names_in_use ) {
			$is_spam     = true;
			$spam_reason = 'Only contains Paragraph blocks.';
		}
	}

	// Run it past Akismet.
	if ( ! $is_spam && is_callable( array( 'Akismet', 'rest_auto_check_comment' ) ) ) {
		$current_user = wp_get_current_user();

		$akismet_payload = array(
			'comment_post_ID'      => 0,
			'comment_type'         => 'pattern_submission',
			// Disabled as logged in users get bonus points I think, which we don't want.
			// 'user_ID'           => get_current_user_id(),
			'comment_author'       => $current_user->display_name ?: $current_user->user_login,
			'comment_author_email' => $current_user->user_email,
			'comment_author_url'   => '',
			'comment_content'      => $combined_strings,
			'comment_content_raw'  => $content,
			'permalink'            => get_permalink( $post ),
		);

		$akismet = \Akismet::rest_auto_check_comment( $akismet_payload );
		if ( is_wp_error( $akismet ) ) {
			$akismet = array( 'akismet_result' => 'discard' );
		}

		$is_spam = (
			isset( $akismet['akismet_result'] ) &&
			// true: spam, discard: 100% spam no-question.
			( 'true' === $akismet['akismet_result'] || 'discard' === $akismet['akismet_result'] )
		);
		if ( $is_spam ) {
			$spam_reason = 'Akismet has detected this Pattern as spam.';
		}
	}

	// Testing keyword. Case-sensitive.
	if ( ! $is_spam && str_contains( $combined_strings, 'PatternDirectorySpamTest' ) ) {
		$is_spam     = true;
		$spam_reason = 'Includes the spam trigger word: PatternDirectorySpamTest';
	}

	// If it's been detected as spam, flag it as pending-review.
	if ( $is_spam ) {
		$prepared_post->post_status = SPAM_STATUS;

		// Add a note explaining why this post is in pending, if it's due to spam.
		if ( function_exists( '\WordPressdotorg\InternalNotes\create_note' ) ) {
			\WordPressdotorg\InternalNotes\create_note(
				$prepared_post->ID,
				array(
					'post_author'  => get_user_by( 'login', 'wordpressdotorg' )->ID ?? 0,
					'post_excerpt' => $spam_reason,
				)
			);
		}
	}

	return $prepared_post;
}
