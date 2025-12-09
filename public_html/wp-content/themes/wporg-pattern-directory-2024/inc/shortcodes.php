<?php
namespace WordPressdotorg\Theme\Pattern_Directory_2024;

/**
 * Shortcode to display an edit link for the current pattern
 */
add_shortcode(
	'pattern_edit_link',
	function() {
		$post_id = get_the_ID();
		return site_url( "pattern/$post_id/edit/" );
	}
);

/**
 * Shortcode to display an edit link for the current pattern
 */
add_shortcode(
	'pattern_draft_link',
	function() {
		$post_id = get_the_ID();
		return add_query_arg(
			array(
				'action' => 'draft',
				'_wpnonce' => wp_create_nonce( 'draft-' . $post_id ),
			),
			get_the_permalink()
		);
	}
);
