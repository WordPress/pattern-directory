<?php
namespace WordPressdotorg\Pattern_Translations;

use WP_CLI;
use WP_CLI_Command;

WP_CLI::add_command( 'patterns', __NAMESPACE__ . '\WP_CLI_Patterns' );

/**
 * WordPress.org Patterns
 */
class WP_CLI_Patterns extends WP_CLI_Command {
	/**
	 * Output json representation of the selected site patterns, optionally localized
	 *
	 * @example wp patterns json --post=example
	 * @subcommand json
	 * @synopsis [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--locale=<slug>]
	 */
	public function json( $_, $args ) {
		$patterns = $this->get_patterns_or_exit( $args );

		// Flatten parent to just being the slug.
		array_walk( $patterns, function( $pattern ) {
			$pattern->parent = $pattern->parent->name ?? $pattern->parent;
		} );

		WP_CLI::log( json_encode( $patterns, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Output the combined HTML of the selected patterns, optionally localized
	 *
	 * @example wp patterns html --post=example
	 * @subcommand html
	 * @synopsis [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--locale=<slug>]
	 */
	public function html( $_, $args ) {
		$patterns = $this->get_patterns_or_exit( $args );
		WP_CLI::log( implode( "\n\n", array_column( $patterns, 'html' ) ) );
	}

	/**
	 * Output the extracted strings of the selected patterns, optionally localized
	 *
	 * @example wp patterns strings --post=example
	 * @subcommand strings
	 * @synopsis [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--locale=<slug>]
	 */
	public function strings( $_, $args ) {
		$patterns = $this->get_patterns_or_exit( $args );

		$strings = [];

		foreach ( $patterns as $pattern ) {
			$parser  = new PatternParser( $pattern );
			$strings = array_merge( $strings, $parser->to_strings() );
		}

		WP_CLI::log( implode( "\n", array_unique( $strings ) ) );
	}

	/**
	 * Output a .pot file for tranlsation
	 *
	 * @example wp patterns makepot
	 * @subcommand makepot
	 * @synopsis [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts]
	 */
	public function makepot( $_, $args ) {
		$patterns = $this->get_patterns_or_exit( $args );

		$makepot = new PatternMakepot( $patterns );

		$po = $makepot->makepo();

		WP_CLI::log( $po->export() );
		exit( 0 );
	}

	/**
	 * Import the selected patterns strings into glotpress
	 *
	 * @example wp --url=https://translate.wordpress.org/ patterns glotpress-import
	 * @subcommand glotpress-import
	 * @synopsis [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--save]
	 */
	public function glotpress_import( $_, $args ) {
		$patterns = $this->get_patterns_or_exit( $args );

		$makepot = new PatternMakepot( $patterns );
		WP_CLI::log( $makepot->import( isset( $args['save'] ) ) );
		exit( 0 );
	}

	/**
	 * All patterns commands accept an array of patterns and all commands leverage the same pattern selection flags.
	 */
	private function get_patterns_or_exit( $args ) {
		$post = $args['post'] ?? false;
		$post_ids = $args['post-ids'] ?? false;
		$post_slugs = $args['post-slugs'] ?? false;
		$all_posts = isset( $args['all-posts'] );
		$locale = $args['locale'] ?? false;

		$query = [];

		if ( false !== $post && ctype_digit( $post ) ) {
			$query['p'] = $post;
		} elseif ( false !== $post && ! ctype_digit( $post ) ) {
			$query['name'] = $post;
		} elseif ( false !== $post_ids ) {
			$post_ids = explode( ',', $post_ids );
			$post_ids = array_filter( $post_ids, 'ctype_digit' );
			$query['post__in'] = $post_ids;
			$query['orderby'] = 'post__in'; // Ensure mostly repeatable - todo? refactor this code into lib so its testable
		} elseif ( false !== $post_slugs ) {
			$query['post_name__in'] = explode( ',', $post_slugs );
			$query['orderby'] = 'post_name__in'; // Ensure mostly repeatable
		} elseif ( $all_posts ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElseif -- empty state OK.
			// send it all
		} else {
			WP_CLI::error( 'A post selector is required, did you mean to add --all-posts to this command? ' );
			exit( 1 );
		}

		$query['post_status'] = 'publish';

		$patterns = Pattern::get_patterns( $query );

		if ( empty( $patterns ) ) {
			WP_CLI::error( 'No patterns found for args: ' . json_encode( $args ) . ', query: ' . json_encode( $query ) );
			exit( 1 );
		}

		if ( $locale ) {
			$patterns = translate_patterns_to( $patterns, $locale );
		}

		return $patterns;
	}

}
