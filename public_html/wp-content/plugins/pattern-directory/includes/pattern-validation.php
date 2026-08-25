<?php

namespace WordPressdotorg\Pattern_Directory\Pattern_Validation;

use WordPressdotorg\Pattern_Translations\Pattern as Translations_Pattern;
use WordPressdotorg\Pattern_Translations\PatternParser as Translations_PatternParser;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };

add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_content', 10, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_block_context', 10, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_block_attributes', 10, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_title', 11, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_status', 11, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_parent', 11, 2 );
add_filter( 'rest_pre_insert_' . POST_TYPE, __NAMESPACE__ . '\validate_against_spam', 20, 2 );
add_action( 'transition_post_status', __NAMESPACE__ . '\note_spam_status', 10, 3 );

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
	$allowed_empty = array( 'core/separator', 'core/spacer' );
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
	$invalid_blocks = array_filter( $all_blocks, function ( $block ) use ( $registry ) {
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
 * Reject blocks used outside the parent or ancestor context they are registered for.
 *
 * `validate_content()` accepts any registered block wherever it sits; this adds the nesting rule the
 * editor enforces, so a block only appears where it was built to (e.g. `core/page-list-item` inside
 * `core/page-list`). Out of context such blocks expose attributes their parent should populate.
 *
 * @param object           $prepared_post The post object about to be inserted.
 * @param \WP_REST_Request $request       The request.
 *
 * @return object|\WP_Error The post object, or an error if a block is used out of context.
 */
function validate_block_context( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	// No content on the request means this is an update to an existing pattern's other fields.
	if ( ! isset( $prepared_post->post_content ) ) {
		return $prepared_post;
	}

	$registry = \WP_Block_Type_Registry::get_instance();
	$blocks   = parse_blocks( $prepared_post->post_content );

	// A pattern is inserted into post content, so its top level sits in a `core/post-content` context.
	if ( ! block_context_is_valid( $blocks, array( 'core/post-content' ), $registry ) ) {
		return new \WP_Error(
			'rest_pattern_invalid_block_context',
			__( 'Pattern content contains a block used outside the block it belongs to.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	return $prepared_post;
}

/**
 * Recursively check that every block satisfies its registered `parent` and `ancestor` constraints.
 *
 * Both constraints are satisfied when a named block appears anywhere among the ancestors. The editor's
 * `parent` rule is stricter (the direct parent), but it also accepts a block via a container's
 * `allowedBlocks`, which core blocks declare in editor JavaScript the server cannot see -- a submenu's
 * `core/navigation-link` has parent `core/navigation` yet sits inside `core/navigation-submenu`. Matching
 * on ancestors accepts everything the editor produces while still rejecting blocks used with no valid
 * parent in sight. Unregistered blocks are left to `validate_content()`.
 *
 * @param array                   $blocks    Parsed blocks at the current depth.
 * @param string[]                $ancestors Block names of this level's ancestors, outermost first.
 * @param \WP_Block_Type_Registry $registry  The block type registry.
 *
 * @return bool Whether every block in the tree is used in a valid context.
 */
function block_context_is_valid( $blocks, $ancestors, $registry ) {
	foreach ( $blocks as $block ) {
		// Freeform gaps parse to a null name; no constraint to check.
		if ( empty( $block['blockName'] ) ) {
			continue;
		}

		$block_type = $registry->get_registered( $block['blockName'] );
		if ( is_null( $block_type ) ) {
			continue;
		}

		if ( ! empty( $block_type->parent ) && ! array_intersect( (array) $block_type->parent, $ancestors ) ) {
			return false;
		}

		if ( ! empty( $block_type->ancestor ) && ! array_intersect( (array) $block_type->ancestor, $ancestors ) ) {
			return false;
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$child_ancestors   = $ancestors;
			$child_ancestors[] = $block['blockName'];
			if ( ! block_context_is_valid( $block['innerBlocks'], $child_ancestors, $registry ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Reject executable URL schemes carried in block attributes.
 *
 * Block attributes are stored as JSON in the block-delimiter comment, so KSES never sanitises them as
 * URLs. A URL-bearing attribute can therefore carry an unvetted `javascript:` value; reject any URL
 * attribute whose value resolves to a script protocol.
 *
 * @param object           $prepared_post The post object about to be inserted.
 * @param \WP_REST_Request $request       The request.
 *
 * @return object|\WP_Error The post object, or an error if an attribute carries a script URL.
 */
function validate_block_attributes( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	if ( ! isset( $prepared_post->post_content ) ) {
		return $prepared_post;
	}

	if ( blocks_have_unsafe_attribute( parse_blocks( $prepared_post->post_content ) ) ) {
		return new \WP_Error(
			'rest_pattern_unsafe_attribute',
			__( 'Pattern content contains a block attribute with an unsafe URL.', 'wporg-patterns' ),
			array( 'status' => 400 )
		);
	}

	return $prepared_post;
}

/**
 * Recursively test whether any block in the tree carries an attribute with a script URL.
 *
 * @param array $blocks Parsed blocks at the current depth.
 *
 * @return bool Whether any block attribute resolves to a script protocol.
 */
function blocks_have_unsafe_attribute( $blocks ) {
	foreach ( $blocks as $block ) {
		if ( isset( $block['attrs'] ) && attribute_has_unsafe_scheme( $block['attrs'] ) ) {
			return true;
		}

		if ( ! empty( $block['innerBlocks'] ) && blocks_have_unsafe_attribute( $block['innerBlocks'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether a block attribute value resolves to a script URL (`javascript:` or `vbscript:`).
 *
 * Recurses through array and object values (`style`, for example), reading the scheme from the text
 * before the first colon after decoding entities and stripping the whitespace and control characters a
 * browser ignores. Only values stored under a URL-carrying key (`url`, `href`, `src`, `link` and their
 * variants) are read as URLs; every URL attribute of the core blocks the directory allows uses such a
 * name, and text values such as a "JavaScript:"-prefixed group label must not be refused as URLs.
 *
 * @param mixed $value  A block attribute value, or a nested part of one.
 * @param bool  $is_url Whether the value sits under a URL-carrying key. List items inherit it.
 *
 * @return bool Whether the value resolves to a script protocol.
 */
function attribute_has_unsafe_scheme( $value, $is_url = false ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			$item_is_url = is_string( $key ) ? (bool) preg_match( '/url|href|src|link/i', $key ) : $is_url;
			if ( attribute_has_unsafe_scheme( $item, $item_is_url ) ) {
				return true;
			}
		}
		return false;
	}

	if ( ! $is_url || ! is_string( $value ) || '' === $value ) {
		return false;
	}

	/*
	 * Browsers decode numeric character references in an attribute even without the trailing semicolon,
	 * which `html_entity_decode()` never does, so decode those first. Only ASCII matters for reading a
	 * scheme; anything else (including NUL and out-of-range codepoints) becomes U+FFFD, as in a browser.
	 */
	$decoded = preg_replace_callback(
		'/&#(?:[Xx]([0-9A-Fa-f]+)|([0-9]+));?/',
		function ( $matches ) {
			$codepoint = '' !== $matches[1] ? hexdec( $matches[1] ) : (int) $matches[2];
			return $codepoint > 0 && $codepoint < 0x80 ? chr( $codepoint ) : "\u{FFFD}";
		},
		$value
	);

	// ENT_HTML5 so named entities like `&colon;` decode the same way a browser decodes them in an href.
	$decoded = html_entity_decode( $decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$colon   = strpos( $decoded, ':' );
	if ( false === $colon ) {
		return false;
	}

	// A browser ignores ASCII whitespace and control characters when reading a scheme, so strip them first.
	$scheme = strtolower( preg_replace( '/[\x00-\x20]+/', '', substr( $decoded, 0, $colon ) ) );

	return in_array( $scheme, array( 'javascript', 'vbscript' ), true );
}

/**
 * Validate the pattern title.
 */
function validate_title( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	$post   = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID ) : null;
	$status = isset( $request['status'] ) ? $request['status'] : ( $post ? $post->post_status : '' );

	// Bypass this validation for drafts.
	if ( 'draft' === $status || 'auto-draft' === $status ) {
		return $prepared_post;
	}

	$title = isset( $request['title'] ) ? $request['title'] : ( $post ? $post->post_title : '' );

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
 * Ensures patterns created via the API are either drafts, or use the chosen status set in
 * /wp-admin/options-general.php?page=wporg-pattern-creator. The `unlisted` and spam statuses
 * are moderator-only, both as a target and as a source, so an author can neither self-unlist
 * nor undo a moderator's removal.
 */
function validate_status( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	$post_type      = get_post_type_object( POST_TYPE );
	$target_status  = isset( $request['status'] ) ? $request['status'] : '';
	$current_status = isset( $prepared_post->ID ) ? get_post_status( $prepared_post->ID ) : '';

	// `unlisted` and spam are moderator-set; authors can't leave them. Must stay above the early returns below.
	if (
		in_array( $current_status, array( SPAM_STATUS, UNLISTED_STATUS ), true ) &&
		'' !== $target_status &&
		$current_status !== $target_status &&
		! current_user_can( $post_type->cap->edit_others_posts )
	) {
		return new \WP_Error(
			'rest_pattern_cannot_change_status',
			__( 'Only a directory moderator can change the status of this pattern.', 'wporg-patterns' ),
			array( 'status' => 403 )
		);
	}

	// Drafts are OK.
	if ( in_array( $target_status, array( 'draft', 'auto-draft' ), true ) ) {
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

	return $prepared_post;
}

/**
 * Validate the pattern's parent.
 *
 * `parent` links a translated pattern to its English original and is written only by the translation cron,
 * never by a submitter. Core accepts it over REST because the field is in the schema, and validates only that
 * the id names an existing post — not its type, and not the caller's relationship to it. Left open, an author
 * can point their own submission at any published pattern and have the translation job adopt it.
 *
 * @param object           $prepared_post The post object about to be inserted.
 * @param \WP_REST_Request $request       The request.
 *
 * @return object|\WP_Error The post object, or an error if the parent is not the caller's to set.
 */
function validate_parent( $prepared_post, $request ) {
	if ( is_wp_error( $prepared_post ) ) {
		return $prepared_post;
	}

	if ( ! isset( $request['parent'] ) ) {
		return $prepared_post;
	}

	$existing       = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID ) : null;
	$current_parent = $existing ? (int) $existing->post_parent : 0;
	$target_parent  = (int) $request['parent'];

	// Re-sending the stored value isn't a write.
	if ( $target_parent === $current_parent ) {
		return $prepared_post;
	}

	$post_type = get_post_type_object( POST_TYPE );
	if ( ! current_user_can( $post_type->cap->edit_others_posts ) ) {
		return new \WP_Error(
			'rest_pattern_cannot_set_parent',
			__( 'Only a directory moderator can set the parent of a pattern.', 'wporg-patterns' ),
			array( 'status' => 403 )
		);
	}

	// A moderator's value still has to name another pattern.
	if ( $target_parent && POST_TYPE !== get_post_type( $target_parent ) ) {
		return new \WP_Error(
			'rest_pattern_invalid_parent',
			__( 'The parent of a pattern must be another pattern.', 'wporg-patterns' ),
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

	/*
	 * `ID` is only set on an update: `WP_REST_Posts_Controller::prepare_item_for_database()` adds it when the
	 * request names an existing post, so on a create there is no stored post to read a status from.
	 */
	$post           = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID ) : null;
	$current_status = $post ? $post->post_status : '';

	/*
	 * An update that omits `status` leaves the pattern at the status it already has, so resolve to that
	 * rather than to nothing. Reading it as "no status" is what let an author publish clean content and then
	 * swap in the real payload with a status-less edit that never reached Akismet.
	 */
	$target_status = isset( $request['status'] ) ? $request['status'] : $current_status;

	// Only patterns that are, or are becoming, publicly visible are worth the check.
	if ( 'publish' !== $target_status && 'pending' !== $target_status ) {
		return $prepared_post;
	}

	/*
	 * An autosave that names no status can't make anything public: the controller either files it as a
	 * revision, throwing the verdict away, or updates the author's own draft while leaving its status alone.
	 * One that does name a status can publish a draft in place, so it still gets checked.
	 */
	if ( ! isset( $request['status'] ) && '/autosaves' === substr( (string) $request->get_route(), -10 ) ) {
		return $prepared_post;
	}

	// Moderators are trusted, the same way `validate_status()` trusts them.
	if ( current_user_can( get_post_type_object( POST_TYPE )->cap->edit_others_posts ) ) {
		return $prepared_post;
	}

	$pattern = array(
		'ID'          => $post->ID ?? 0,
		'post_name'   => $post->post_name ?? '',
		'post_author' => $post->post_author ?? get_current_user_id(),
		'title'       => $prepared_post->post_title ?? ( $post->post_title ?? '' ),
		'content'     => $prepared_post->post_content ?? ( $post->post_content ?? '' ),
		'description' => $request['meta']['wpop_description'] ?? ( $post ? ( $post->wpop_description ?: '' ) : '' ),
		'keywords'    => $request['meta']['wpop_keywords'] ?? ( $post ? ( $post->wpop_keywords ?: '' ) : '' ),
	);

	list( $is_spam, $spam_reason ) = check_for_spam( $pattern );

	if ( $is_spam ) {
		// Demoting an existing pattern on a heuristic is unrecoverable for its author, so refuse the edit.
		if ( in_array( $current_status, array( 'publish', 'pending' ), true ) ) {
			return new \WP_Error(
				'rest_pattern_spam_detected',
				__( 'These changes were caught by the spam filter, so they have not been saved. Your pattern is unchanged.', 'wporg-patterns' ),
				array( 'status' => 400 )
			);
		}

		// Anything not yet public goes to the moderation queue as before.
		$prepared_post->post_status = SPAM_STATUS;
		spam_reason( $prepared_post->ID ?? 0, $spam_reason );
	}

	return $prepared_post;
}

/**
 * Hold the reason a pattern was flagged as spam, until its status is actually saved.
 *
 * `validate_against_spam()` decides before anything is written, and that decision can be discarded, so the
 * note has to wait. Keyed by pattern because a single request can write more than one -- a `batch/v1`
 * envelope, a WP-CLI import loop -- and one pattern's reason must not be noted against another. Reading
 * consumes, so an unconsumed reason can't leak into a later write.
 *
 * @param int         $pattern_id The pattern the reason belongs to, 0 while it is still being created.
 * @param string|null $reason     Reason to store, or null to read and consume the stored one.
 *
 * @return string The stored reason, or '' if there isn't one for this pattern.
 */
function spam_reason( $pattern_id, $reason = null ) {
	static $reasons = array();

	$key = (int) $pattern_id;

	if ( null !== $reason ) {
		$reasons[ $key ] = $reason;

		return $reason;
	}

	if ( ! isset( $reasons[ $key ] ) ) {
		return '';
	}

	$stored = $reasons[ $key ];
	unset( $reasons[ $key ] );

	return $stored;
}

/**
 * Record why a pattern was quarantined, once that status has actually been saved.
 *
 * @param string   $new_status The status the pattern moved to.
 * @param string   $old_status The status it moved from.
 * @param \WP_Post $post       The pattern.
 *
 * @return void
 */
function note_spam_status( $new_status, $old_status, $post ) {
	if ( POST_TYPE !== $post->post_type || SPAM_STATUS !== $new_status || $new_status === $old_status ) {
		return;
	}

	$reason = spam_reason( $post->ID );

	// A pattern flagged as it was created had no ID to record against.
	if ( ! $reason && in_array( $old_status, array( 'new', 'auto-draft' ), true ) ) {
		$reason = spam_reason( 0 );
	}

	if ( ! $reason ) {
		return;
	}

	if ( function_exists( '\WordPressdotorg\InternalNotes\create_note' ) ) {
		\WordPressdotorg\InternalNotes\create_note(
			$post->ID,
			array(
				'post_author'  => get_user_by( 'login', 'wordpressdotorg' )->ID ?? 0,
				'post_excerpt' => $reason,
			)
		);
	}
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
