<?php
namespace WordPressdotorg\Pattern_Translations;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use GlotPress_Translate_Bridge;

class Pattern {
	public $ID = null;
	public $title = '';
	public $name = '';
	public $description = '';
	public $html = '';
	public $source_url = '';
	public $keywords = '';

	public $locale = 'en_US';
	public $parent = false;

	/**
	 * Translate a Pattern into a specific locale.
	 *
	 * @param string $locale The locale to translate this Pattern to.
	 * @return Pattern|false A new Pattern object upon success, or false if no translated fields were available.
	 */
	public function to_locale( string $locale ) /* PHP8 : Pattern|bool */ {
		if ( 'en_US' !== $this->locale ) {
			if ( $this->parent && 'en_US' === $this->parent->locale ) {
				$parent = $this->parent;
			} else {
				$parent = self::from_post( get_post( $this->ID ) );
			}
		} else {
			$parent = $this;
		}
		$translated         = clone $parent;
		$translated->parent = $parent;

		// to convert from a Translated Pattern to en_US.
		if ( 'en_US' === $locale ) {
			$translated->parent = false;
			return $translated;
		}

		switch_to_locale( $locale );

		$parser = new PatternParser( $translated );

		$translations = [];
		$translated   = false;
		foreach ( $parser->to_strings() as $string ) {
			$translations[ $string ] = apply_filters( 'gettext', GlotPress_Translate_Bridge::translate( $string, GLOTPRESS_PROJECT ), 'wporg-pattern' );

			// Consider any string change to be a translation.
			if ( $string !== $translations[ $string ] ) {
				$translated = true;
			}
		}

		restore_current_locale();

		// Are there any translations?
		if ( ! $translated ) {
			return false;
		}

		$translated         = $parser->replace_strings_with_kses( $translations );
		$translated->locale = $locale;
		// Reset the ID.
		$translated->ID     = 0;

		// Find the actual post ID of the translated pattern
		$children = get_posts( [
			'post_parent' => $parent->ID,
			'post_type'   => POST_TYPE,
			'post_status' => 'any',
			'meta_query'  => [
				[
					'key'   => 'wpop_locale',
					'value' => $locale,
				],
			],
		] );
		if ( $children ) {
			$post = array_shift( $children );
			$translated->ID   = $post->ID;
			$translated->name = $post->post_name; // ???
		}

		return $translated;
	}

	/**
	 * Create a new Pattern object from a WP_Post object for translation purposes.
	 *
	 * @param \WP_Post $post The post object.
	 * @return Pattern The Pattern object.
	 */
	public static function from_post( \WP_Post $post ) : Pattern {
		$pattern              = new Pattern();
		$pattern->ID          = $post->ID;
		$pattern->title       = $post->post_title;
		$pattern->name        = $post->post_name;
		$pattern->description = $post->wpop_description;
		$pattern->keywords    = $post->wpop_keywords;
		$pattern->html        = $post->post_content;
		$pattern->source_url  = get_permalink( $post );
		$pattern->locale      = 'en_US';

		return $pattern;
	}

	/**
	 * Fetch an array of Pattern objects based on a WP_Query query.
	 *
	 * @param array $args The WP_Query args.
	 * @return array An array of Pattern objects.
	 */
	public static function get_patterns( array $args = [] ) : array {
		$defaults = [
			'post_type'      => POST_TYPE,
			// Note: This must be set for cli context, in isolated test context this is defaulted to 'publish'
			// Prevents unexpected patterns in translations
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => [
				'post_date' => 'DESC',
			],
			// Only select en_US patterns.
			'meta_query' => [
				[
					'key'   => 'wpop_locale',
					'value' => 'en_US',
				],
			],
		];

		$options = wp_parse_args( $args, $defaults );

		$query    = new \WP_Query();
		$patterns = $query->query( $options );

		wp_reset_postdata();

		if ( 'ids' !== $query->get( 'fields' ) ) {
			$patterns = array_map( [ self::class, 'from_post' ], $patterns );
		}

		return $patterns;
	}
}
