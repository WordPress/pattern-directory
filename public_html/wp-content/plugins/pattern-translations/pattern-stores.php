<?php

namespace WordPressdotorg\Pattern_Translations;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;


class Pattern {
	public $ID = null;
	public $site_id = null;
	public $title = '';
	public $name = '';
	public $description = '';
	public $html = '';
	public $categories = []; // slug => [ slug, title, description ]
	public $tags = []; // slug => [ slug, title, description ]
	public $source_url = '';
	public $modified_date = '';
}

class PatternStores {
	private $site_id;

	public function __construct( $site_id ) {
		$this->site_id = $site_id ?: get_current_blog_id();
	}

	public function get_pattern( array $args = [] ) : ?Pattern {
		$patterns = $this->get_patterns( wp_parse_args( $args, [ 'numberposts' => 1 ] ) );

		$pattern = reset( $patterns );

		if ( empty( $pattern ) ) {
			return null;
		}

		return $pattern;
	}

	public function get_patterns( array $args = [] ) : array {
		return temporary_switch_to_blog(
			$this->site_id,
			function () use ( $args ) {

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
		);
	}

	// In this class (specifically not public on Pattern) because calling within
	// the context allows us to:
	// - find the terms.
	// - loop on this function without duplicating switching.
	// - Keep \WP_Post !== Pattern.
	public function new_pattern_from_post( \WP_Post $post ) : Pattern {
		$pattern = new Pattern();
		$pattern->ID = $post->ID;
		$pattern->site_id = $this->site_id;
		$pattern->title = $post->post_title;
		$pattern->name = $post->post_name;
		$pattern->description = $post->post_excerpt;
		$pattern->html = $post->post_content;
		$pattern->source_url = $post->guid;
		$pattern->modified_date = get_the_modified_date( 'Y-m-d H:i:s', $post );

		$categories = get_the_terms( $post, 'category' );
		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			$categories = [];
		}

		foreach ( $categories as $term ) {
			$pattern->categories[ $term->slug ] = [
				'slug' => $term->slug,
				'title' => $term->name,
				'description' => $term->description,
			];
		}

		$tags = get_the_terms( $post, 'post_tag' );
		if ( empty( $tags ) || is_wp_error( $tags ) ) {
			$tags = [];
		}

		foreach ( $tags as $term ) {
			$pattern->tags[ $term->slug ] = [
				'slug' => $term->slug,
				'title' => $term->name,
				'description' => $term->description,
			];
		}

		return $pattern;
	}
}
