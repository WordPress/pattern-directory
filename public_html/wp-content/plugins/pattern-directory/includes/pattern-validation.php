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

	// Most dynamic blocks don't need custom content, but there are some
	// exceptions that should go through the rest of the checks.
	if (
		$block_type->is_dynamic() &&
		! in_array( $block['blockName'], array( 'core/image' ) )
	) {
		return true;
	}

	// Paragraphs are a special case, these should never be empty.
	if ( 'core/paragraph' === $block['blockName'] ) {
		$block_content = strip_basic_html( $block['innerHTML'] );
		if ( empty( $block_content ) ) {
			return false;
		}
	}

	// Exceptions - these contain no content and maybe no attributes.
	$allowed_empty = [ 'core/separator', 'core/spacer' ];
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
	$blocks_queue = $blocks;
	$all_blocks = array();

	// Loop over all the nested blocks to flatten the block list into 1 dimension.
	while ( count( $blocks_queue ) > 0 ) { // phpcs:ignore -- inline count OK.
		$block = array_shift( $blocks_queue );
		array_push( $all_blocks, $block );
		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				array_push( $blocks_queue, $inner_block );
			}
		}
	}

	// Check that each block in the list has a blockName and is registered.
	$registry = \WP_Block_Type_Registry::get_instance();
	$invalid_blocks = array_filter( $all_blocks, function( $block ) use ( $registry ) {
		$block_type = $registry->get_registered( $block['blockName'] );
		return is_null( $block['blockName'] ) || is_null( $block_type );
	} );

	if ( count( $invalid_blocks ) ) {
		return new \WP_Error(
			'rest_pattern_invalid_blocks',
			__( 'Pattern content contains invalid blocks. Patterns shared on the Pattern Directory can only use core blocks.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	// Next, filter out any empty blocks
	$real_blocks = array_filter( $all_blocks, __NAMESPACE__ . '\is_not_empty_block' );

	// Check that we have at least one non-empty block.
	if ( ! count( $real_blocks ) ) {
		return new \WP_Error(
			'rest_pattern_empty_blocks',
			__( 'Pattern content contains only empty or default blocks.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	// Check that we have at least three non-empty blocks (and show a different error message).
	if ( count( $real_blocks ) < 3 ) {
		return new \WP_Error(
			'rest_pattern_insufficient_blocks',
			__( 'Pattern content contains less than three blocks. Patterns should combine multiple blocks for interesting layouts.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	// Check that there are fewer than 75 blocks.
	if ( count( $real_blocks ) > 75 ) {
		return new \WP_Error(
			'rest_pattern_extra_blocks',
			__( 'Pattern content contains over 75 blocks. Patterns should not replicate full pages or blog posts, try breaking your pattern into smaller submissions.', 'wporg-patterns' ),
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

	$title = isset( $request['title'] ) ? $request['title'] : get_the_title( $prepared_post->ID );

	// A title exists, but is empty -- invalid.
	if ( isset( $title ) && empty( trim( $title ) ) ) {
		return new \WP_Error(
			'rest_pattern_empty_title',
			__( 'A pattern title is required.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	if ( ! is_title_valid( $title ) ) {
		return new \WP_Error(
			'rest_pattern_invalid_title',
			__( 'Pattern title is invalid. The pattern title should describe the pattern.', 'wporg-patterns' ),
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

	$post_type      = get_post_type_object( POST_TYPE );
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

	// Skip validation if the user is a moderator.
	if ( current_user_can( $post_type->cap->edit_others_posts ) ) {
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
	if ( SPAM_STATUS === $current_status && SPAM_STATUS !== $target_status ) {
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

	// Run spam checks for publish & pending patterns.
	if ( 'publish' !== $target_status && 'pending' !== $target_status ) {
		return $prepared_post;
	}

	$post = get_post( $prepared_post->ID );

	$pattern = array(
		'ID'          => $post->ID,
		'post_name'   => $post->post_name,
		'post_author' => $post->post_author,
		'title'       => $prepared_post->post_title ?? $post->post_title,
		'content'     => $prepared_post->post_content ?? $post->post_content,
		'description' => $request['meta']['wpop_description'] ?? ( $post->wpop_description ?: '' ),
		'keywords'    => $request['meta']['wpop_keywords'] ?? ( $post->wpop_keywords ?: '' ),
	);

	list( $is_spam, $spam_reason ) = check_for_spam( $pattern );

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

/**
 * Helper function to check for spam.
 *
 * @param array $post
 * @return array {
 *    @type boolean $is_spam
 *    @type string  $spam_reason
 * }
 */
function check_for_spam( $post ) {
	// Stringify.
	if ( ! class_exists( '\WordPressdotorg\Pattern_Translations\Pattern' ) ) {
		// This is just a fall-back for local environments where the Translator isn't active.
		// not designed to be used in production.
		$strings = array(
			$post['title'],
			$post['description'],
			wp_strip_all_tags( $post['content'] ),
			$post['keywords'],
		);
	} else {
		$pattern              = new Translations_Pattern();
		$pattern->ID          = $post['ID'];
		$pattern->title       = $post['title'];
		$pattern->name        = $post['post_name'];
		$pattern->description = $post['description'];
		$pattern->keywords    = $post['keywords'];
		$pattern->html        = $post['content'];
		$pattern->locale      = get_locale();

		$parser  = new Translations_PatternParser( $pattern );
		$strings = $parser->to_strings();
	}

	// Combine strings for ease of use.
	$combined_strings = implode( "\n", $strings );

	// Not yet detected as spam.
	$is_spam     = false;
	$spam_reason = '';

	// Treat Paragraph-only submissions as likely spam.
	if ( ! $is_spam ) {
		// Only fetches the top-level of blocks, we're only
		$block_names_in_use = array_filter(
			array_unique(
				wp_list_pluck(
					parse_blocks( $post['content'] ),
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
		$author = get_user_by( 'ID', $post['post_author'] );
		if ( ! $author ) {
			$author = wp_get_current_user();
		}

		$akismet_payload = array(
			'comment_post_ID'      => 0,
			'comment_type'         => 'pattern_submission',
			// Disabled as logged in users get bonus points I think, which we don't want.
			// 'user_ID'           => get_current_user_id(),
			'comment_author'       => $author->display_name ?: $author->user_login,
			'comment_author_email' => $author->user_email,
			'comment_author_url'   => '',
			'comment_content'      => $combined_strings,
			'comment_content_raw'  => $post['content'],
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

	return array( $is_spam, $spam_reason );
}

/**
 * Helper function to check for a valid pattern title.
 *
 * @param string $title
 * @return boolean
 */
function is_title_valid( $title ) {
	// Check title against a list of disallowed words.
	// Note the space after `test ` to avoid matching "testimonial".
	$disallow_list = array( 'test ', 'testing', 'my pattern', 'wordpress', 'example' );

	if ( 'test' === strtolower( $title ) ) {
		return false;
	}

	foreach ( $disallow_list as $disallowed ) {
		if ( false !== stripos( $title, $disallowed ) ) {
			return false;
		}
	}

	return true;
}
