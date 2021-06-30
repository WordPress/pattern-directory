<?php

namespace WordPressdotorg\Pattern_Translations;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

class Pattern {
	public $ID = null;
	public $title = '';
	public $name = '';
	public $description = '';
	public $html = '';
	public $source_url = '';

	public static function from_post( \WP_Post $post ) : Pattern {
		$pattern              = new Pattern();
		$pattern->ID          = $post->ID;
		$pattern->title       = $post->post_title;
		$pattern->name        = $post->post_name;
		$pattern->description = $post->wpop_description; // wpop_description Meta key
		$pattern->html        = $post->post_content;
		$pattern->source_url  = get_permalink( $post );

		return $pattern;
	}

	public static function get_patterns( array $args = [] ) : array {
		$defaults = [
			'post_type'      => POST_TYPE,
			// Note: this must be set for cli context, in isolated test context this is defaulted to 'publish'
			'post_status'    => 'publish', // prevent leaking draft patterns to makepot etc
			'posts_per_page' => -1,
			'orderby'        => [
				'post_date' => 'DESC',
			],
		];

		$options = wp_parse_args( $args, $defaults );

		$query = new \WP_Query();
		$posts = $query->query( $options );

		wp_reset_postdata();

		$patterns = array_map( [ self::class, 'from_post' ], $posts );

		return $patterns;
	}
}
