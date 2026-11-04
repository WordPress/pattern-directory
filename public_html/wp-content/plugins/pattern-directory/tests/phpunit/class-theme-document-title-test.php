<?php
/**
 * Test the theme's document titles.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;

require_once __DIR__ . '/trait-loads-theme.php';

/**
 * Favorites and My Patterns name the filters they show, as category archives do (#465).
 */
class Theme_Document_Title_Test extends WP_UnitTestCase {
	use Loads_Theme;

	/**
	 * Create the pages and categories, and load the theme's functions.
	 *
	 * @param WP_UnitTest_Factory $factory Test data factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		$factory->post->create(
			array(
				'post_type'  => 'page',
				'post_name'  => 'favorites',
				'post_title' => 'Favorites',
			)
		);
		$factory->post->create(
			array(
				'post_type'  => 'page',
				'post_name'  => 'my-patterns',
				'post_title' => 'My Patterns',
			)
		);
		foreach ( array( 'Gallery', 'Header' ) as $name ) {
			$factory->term->create(
				array(
					'taxonomy' => 'wporg-pattern-category',
					'name'     => $name,
					'slug'     => strtolower( $name ),
				)
			);
		}

		self::load_theme();
	}

	/**
	 * Register just the theme hooks under test.
	 */
	public function set_up(): void {
		parent::set_up();

		add_filter( 'query_vars', 'WordPressdotorg\Theme\Pattern_Directory_2024\add_patterns_query_vars' );
		add_filter( 'document_title_parts', 'WordPressdotorg\Theme\Pattern_Directory_2024\set_document_title' );
	}

	/**
	 * Catches filtered pages titled by the page alone, or a title that leaves out or breaks on a filter.
	 *
	 * @dataProvider data_filtered_pages
	 *
	 * @param string $query    The page request.
	 * @param string $expected The document title.
	 */
	public function test_filtered_page_titles_name_the_filters( string $query, string $expected ): void {
		$this->go_to( home_url( '/?' . $query ) );

		$this->assertSame( $expected, wp_get_document_title() );
	}

	/**
	 * Page requests and the titles they should get.
	 *
	 * @return array[]
	 */
	public function data_filtered_pages(): array {
		return array(
			'favorites in a category'     => array( 'pagename=favorites&pattern-categories=gallery', 'Favorites: Gallery &#8211; WordPress.org' ),
			'favorites in two categories' => array( 'pagename=favorites&pattern-categories[]=gallery&pattern-categories[]=header', 'Favorites: Gallery, Header &#8211; WordPress.org' ),
			'my patterns by status'       => array( 'pagename=my-patterns&status=pending', 'My Patterns: Pending &#8211; WordPress.org' ),
			'my patterns, status array'   => array( 'pagename=my-patterns&status[]=draft', 'My Patterns &#8211; WordPress.org' ),
			'favorites, not filtered'     => array( 'pagename=favorites', 'Favorites &#8211; WordPress.org' ),
		);
	}
}
