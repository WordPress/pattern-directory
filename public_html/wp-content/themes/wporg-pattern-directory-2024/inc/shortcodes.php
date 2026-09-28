<?php
/**
 * Shortcodes for the Pattern Directory theme.
 *
 * @package WordPress\Pattern_Directory
 */

namespace WordPressdotorg\Theme\Pattern_Directory_2024;

/**
 * Shortcode to display an edit link for the current pattern
 */
add_shortcode(
	'pattern_edit_link',
	function () {
		$post_id = get_the_ID();
		return esc_url( site_url( "pattern/$post_id/edit/" ) );
	}
);
