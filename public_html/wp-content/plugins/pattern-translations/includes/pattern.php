<?php
namespace WordPressdotorg\Pattern_Translations;

use GlotPress_Translate_Bridge;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

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

		$translations = array();
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

		$existing = self::find_existing_translation( (int) $parent->ID, $locale );
		if ( $existing ) {
			$translated->ID   = $existing->ID;
			$translated->name = $existing->post_name; // Preserve the existing translation's slug.
		}

		return $translated;
	}

	/**
	 * Find the pattern this pipeline previously created as the $locale translation of $parent_id.
	 *
	 * `post_parent` and `wpop_locale` are both writable by any submitter on their own pattern, so the pair
	 * alone is an attacker-controlled claim rather than an identification. `wpop_is_translation` is written
	 * only by `create_or_update_translated_pattern()` and is not exposed over REST, so requiring it keeps the
	 * job from adopting a user's own post and overwriting it with the parent's author and status.
	 *
	 * @param int    $parent_id The English original's post ID.
	 * @param string $locale    The locale to find the existing translation for.
	 *
	 * @return \WP_Post|null The existing translation, or null if this pipeline has not created one.
	 */
	public static function find_existing_translation( int $parent_id, string $locale ): ?\WP_Post {
		$children = get_posts( array(
			'post_parent' => $parent_id,
			'post_type'   => POST_TYPE,
			'post_status' => 'any',
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'   => 'wpop_locale',
					'value' => $locale,
				),
				array(
					'key'   => 'wpop_is_translation',
					'value' => 1,
				),
			),
		) );

		return $children ? array_shift( $children ) : null;
	}

	/**
	 * Create a new Pattern object from a WP_Post object for translation purposes.
	 *
	 * @param \WP_Post $post The post object.
	 * @return Pattern The Pattern object.
	 */
	public static function from_post( \WP_Post $post ): Pattern {
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
	public static function get_patterns( array $args = array() ): array {
		$defaults = array(
			'post_type'      => POST_TYPE,
			// Note: This must be set for cli context, in isolated test context this is defaulted to 'publish'
			// Prevents unexpected patterns in translations
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'post_date' => 'DESC',
			),
			// Only select en_US patterns.
			'meta_query' => array(
				array(
					'key'   => 'wpop_locale',
					'value' => 'en_US',
				),
			),
		);

		$options = wp_parse_args( $args, $defaults );

		$query    = new \WP_Query();
		$patterns = $query->query( $options );

		wp_reset_postdata();

		if ( 'ids' !== $query->get( 'fields' ) ) {
			$patterns = array_map( array( self::class, 'from_post' ), $patterns );
		}

		return $patterns;
	}
}
