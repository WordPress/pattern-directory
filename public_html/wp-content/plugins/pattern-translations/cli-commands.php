<?php

require_once( ABSPATH . '/wp-includes/pomo/po.php' );

WP_CLI::add_command( 'patterns', 'WP_WPCOM_CLI_Patterns' );

use A8C\Lib\Patterns\PatternStores;
use A8C\Lib\Patterns\PatternParser;
use A8C\Lib\Patterns\PatternMakepot;
use A8C\Lib\Patterns\PatternsTranslator;


/**
 * WP.com Patterns
 */
class WP_WPCOM_CLI_Patterns extends WP_WPCOM_CLI_Command {
	/**
	 * Output json representation of the selected site patterns, optionally localized
	 *
	 * @example wp patterns json --site=wpcompatterns.wordpress.com --post=example
	 * @subcommand json
	 * @synopsis --site=<site-url-or-id> [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--homepage] [--post-type=<override>] [--locale=<slug>]
	 */
	public function json( $_, $args ) {
		$patterns = $this->get_patterns_or_exit( $args );
		WP_CLI::log( json_encode( $patterns, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Output the combined HTML of the selected patterns, optionally localized
	 *
	 * @example wp patterns html --site=wpcompatterns.wordpress.com --post=example
	 * @subcommand html
	 * @synopsis --site=<site-url-or-id> [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--homepage] [--post-type=<override>] [--locale=<slug>]
	 */
	public function html( $_, $args ) {
		$patterns = $this->get_patterns_or_exit( $args );
		WP_CLI::log( implode( "\n\n", array_column( $patterns, 'html' ) ) );
	}

	/**
	 * Output the extracted strings of the selected patterns, optionally localized
	 *
	 * @example wp patterns strings --site=wpcompatterns.wordpress.com --post=example
	 * @subcommand strings
	 * @synopsis --site=<site-url-or-id> [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--homepage] [--post-type=<override>] [--locale=<slug>]
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
	 * @example wp patterns makepot --site=wpcompatterns.wordpress.com
	 * @subcommand makepot
	 * @synopsis --site=<site-url-or-id> [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--homepage] [--post-type=<override>] [--include-site-wide-strings]
	 */
	public function makepot( $_, $args ) {
		$patterns = $this->get_patterns_or_exit( $args );

		$makepot = new PatternMakepot( $patterns );

		$po = $makepot->makepo();

		// Optionally grab the site-related strings out of the database
		if ( isset( $args['include-site-wide-strings'] ) ) {
			$site_wide_strings = $this->get_site_wide_strings( $this->get_blog_id( $args['site'] ) );
			$po->merge_originals_with( $site_wide_strings );
		}

		WP_CLI::log( $po->export() );
		exit( 0 );
	}

	/**
	 * Import the selected patterns strings into glotpress
	 *
	 * @example wp patterns glotpress-import --site=wpcompatterns.wordpress.com
	 * @subcommand glotpress-import
	 * @synopsis --site=<site-url-or-id> [--post=<slug-or-id>] [--post-ids=<csv>] [--post-slugs=<csv>] [--all-posts] [--homepage] [--post-type=<override>] [--save]
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
		require_lib( 'patterns' );

		$site = $args['site']; // required
		$post = $args['post'] ?? false;
		$post_ids = $args['post-ids'] ?? false;
		$post_slugs = $args['post-slugs'] ?? false;
		$post_type = $args['post-type'] ?? false;
		$homepage = isset( $args['homepage'] );
		$all_posts = isset( $args['all-posts'] );
		$locale = $args['locale'] ?? false;

		$site_id = $this->get_blog_id( $site );

		if ( is_wp_error( $site_id ) ) {
			WP_CLI::error( "No site_id for $site" );
			exit( 1 );
		}

		$store = PatternStores::get_store( $site_id );

		if ( empty( $store ) ) {
			WP_CLI::error( "No registered store for site id $site ($site_id)" );
			exit( 1 );
		}

		$patterns = [];

		if ( $homepage ) {
			$patterns = array_filter( [ $store->get_homepage() ] );
		} else {
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
			} elseif ( $all_posts ) {
				// send it all
			} else {
				WP_CLI::error( 'A post selector is required, did you mean to add --all-posts to this command? ' );
				exit( 1 );
			}

			if ( isset( $args['post-type'] ) ) {
				$query['post_type'] = $args['post-type'];
			}

			// Also done by patterns lib SiteStore by default but the test that covers this doesn't work
			$query['post_status'] = 'publish';

			$patterns = $store->get_patterns( $query );
		}

		if ( empty( $patterns ) ) {
			WP_CLI::error( 'No patterns found for args: ' . json_encode( $args ) . ', query: ' . json_encode( $query ) );
			exit( 1 );
		}

		if ( false !== $locale ) {
			// used for debugging
			if ( 'rot13' === $locale ) {
				add_filter( 'gettext', 'str_rot13' );
				$locale = 'en';
			}

			// used for debugging
			if ( 'rev' === $locale ) {
				add_filter( 'gettext', [ $this, 'mb_strrev' ] );
				$locale = 'en';
			}

			if ( ! in_array( $locale, array_keys( \WPCom_Languages::get_all_locales_by_slug() ) ) ) {
				WP_CLI::error( "Invalid locale $locale" );
				exit( 1 );
			}

			$translator = new PatternsTranslator( $patterns, $locale );
			$patterns   = $translator->translate();
		}

		return $patterns;
	}

	/**
	 * blog_id_or_url => blog_id - ripped from class.wpcom-json-api.php
	 */
	private function get_blog_id( $blog_id_or_url ) {
		$blog_id_or_url = (string) $blog_id_or_url;

		if ( ctype_digit( $blog_id_or_url ) ) {
			if ( (int) $blog_id_or_url > 0 ) {
				$blog = get_blog_details( (int) $blog_id_or_url );
			} else {
				return new WP_Error( 'unknown_blog', 'Unknown blog', 404 );
			}
		} else {
			$url = urldecode( $blog_id_or_url );
			$url = str_replace( '::', '/', $url );

			$blog = wpcom_get_blog_details_for_url( $url );
		}

		if ( is_string( $blog ) ) {
			$blog = maybe_unserialize( $blog );
			if ( is_string( $blog ) ) {
				$blog = false;
			}
		}

		if ( ! $blog || is_wp_error( $blog ) ) {
			return new WP_Error( 'unknown_blog', 'Unknown blog', 404 );
		}

		return (int) $blog->blog_id;
	}

	// has to be public to be used in a filter
	public function mb_strrev( $string ) {
		preg_match_all( '/./us', $string, $mb_chars );
		return join( '', array_reverse( $mb_chars[0] ) );
	}

	// TODO: move to headstart. This totally doesn't belong here :/
	private function get_site_wide_strings( $site_id ) {
		return temporary_switch_to_blog(
			$site_id,
			function () {
				$site_wide_strings = new \Translations();

				$site_title = get_bloginfo( 'name' );
				$site_description = get_bloginfo( 'description' );
				$site_wide_strings->add_entry(
					[
						'singular'           => $site_title,
						'extracted_comments' => 'The title of a website.',
					]
				);
				$site_wide_strings->add_entry(
					[
						'singular'           => $site_description,
						'extracted_comments' => 'A description of a website.',
					]
				);

				$taxonomy_name_whitelist = [
					'category',
					'link_category',
					'nav_menu',
					'post_tag',
				];

				$taxonomy_slug_whitelist = [
					'category',
					'link_category',
					'post_tag',
				];

				$terms = get_terms();
				foreach ( $terms as $term ) {
					$is_editable_taxonomy = in_array( $term->taxonomy, $taxonomy_slug_whitelist );
					$references = 'nav_menu' === $term->taxonomy
						? [ home_url() . '/wp-admin/nav-menus.php' ]
						: [ home_url() . '/wp-admin/edit-tags.php?taxonomy=' . $term->taxonomy ];
					if ( in_array( $term->taxonomy, $taxonomy_name_whitelist ) ) {
						$site_wide_strings->add_entry(
							[
								'singular'           => $term->name,
								// Not exactly right for other taxonomies, but close enough
								'extracted_comments' => 'Name of a category',
								'references'         => $references,
							]
						);
					}
					if ( $is_editable_taxonomy ) {
						$site_wide_strings->add_entry(
							[
								'singular'           => $term->slug,
								'references'         => $references,
								'extracted_comments' =>
									'A category slug, suitable for appearing in a url. e.g. https://mysite.wordpress.com/category/ë¯¸ë¶„ë¥˜/',
							]
						);
					}
				}
				return $site_wide_strings;
			}
		);
	}
}
