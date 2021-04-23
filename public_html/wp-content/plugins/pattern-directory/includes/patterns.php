<?php

namespace WordPressdotorg\Pattern_Directory\Patterns;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * The main class for handling information about patterns.
 *
 * @package WordPressdotorg\Pattern-directory
 */
class Patterns {

	/**
	 * Fetch the instance of the Patterns class.
	 *
	 * @static
	 */
	public static function instance() {
		static $instance = null;

		return ! is_null( $instance ) ? $instance : $instance = new Patterns();
	}

	/**
	 * Patterns constructor.
	 *
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 * Returns all patterns by author.
	 *
	 * @param integer $author_id Author Id.
	 *
	 * @return \WP_Post[] List of patterns.
	 */
	public function get_patterns_by_author( $author_id ) {
		$args = array(
			'author' => $author_id,
			'post_type' => POST_TYPE,
			'post_status' => 'any',
			'posts_per_page'   => -1,
		);

		return get_posts( $args );
	}

	/**
	 * Returns a list of pending patterns.
	 *
	 * @param \WP_Post[] $pattern_posts List of patterns.
	 *
	 * @return \WP_Post[] List of patterns in pending status.
	 */
	public function filter_pending_patterns( $pattern_posts ) {
		return array_filter( $pattern_posts, function ( $pattern ) {
			return 'pending' === $pattern->post_status;
		});
	}
}
