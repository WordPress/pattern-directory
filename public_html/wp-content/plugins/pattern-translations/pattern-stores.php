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
}

class PatternStores {
	public function get_patterns( array $args = [] ) : array {
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

		$patterns = array_map( [ $this, 'new_pattern_from_post' ], $posts );

		return $patterns;
	}

	// In this class (specifically not public on Pattern) because calling within
	// the context allows us to:
	// - find the terms.
	// - loop on this function without duplicating switching.
	// - Keep \WP_Post !== Pattern.
	public function new_pattern_from_post( \WP_Post $post ) : Pattern {
		$pattern = new Pattern();
		$pattern->ID = $post->ID;
		$pattern->title = $post->post_title;
		$pattern->name = $post->post_name;
		$pattern->description = $post->wpop_description; // wpop_description Meta key
		$pattern->html = $post->post_content;
		// Assume that the reference is relative to the home url of the site
		// TODO: Just make GlotPress make_clickable() the references if it's got no path set.
		$pattern->source_url = str_replace( home_url('/'), '', get_permalink( $post ) );

		return $pattern;
	}
}
