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

/**
 * Favorites and My Patterns name the filters they show, as category archives do (#465).
 */
class Theme_Document_Title_Test extends WP_UnitTestCase {
	/**
	 * The registered hooks as they were before the theme was loaded, or null if it was already loaded.
	 *
	 * @var array|null
	 */
	protected static $hooks_before_theme = null;

	/**
	 * Create the pages and a category, and load the theme as Theme_Pattern_Actions_Test does.
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
		$factory->term->create(
			array(
				'taxonomy' => 'wporg-pattern-category',
				'name'     => 'Gallery',
				'slug'     => 'gallery',
			)
		);

		if ( ! function_exists( '\WordPressdotorg\Theme\Pattern_Directory_2024\set_document_title' ) ) {
			self::$hooks_before_theme = array_map(
				function ( $hook ) {
					return clone $hook;
				},
				$GLOBALS['wp_filter']
			);

			require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/themes/wporg-pattern-directory-2024/functions.php';
		}
	}

	/**
	 * Put back the hooks loading the theme added.
	 */
	public static function tear_down_after_class(): void {
		if ( null !== self::$hooks_before_theme ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring the snapshot taken above.
			$GLOBALS['wp_filter']     = self::$hooks_before_theme;
			self::$hooks_before_theme = null;
		}

		parent::tear_down_after_class();
	}

	/**
	 * Register the theme hooks under test, which an earlier class that loaded the theme may have rolled back.
	 */
	public function set_up(): void {
		parent::set_up();

		add_filter( 'query_vars', 'WordPressdotorg\Theme\Pattern_Directory_2024\add_patterns_query_vars' );
		add_filter( 'document_title_parts', 'WordPressdotorg\Theme\Pattern_Directory_2024\set_document_title' );
	}

	/**
	 * Catches the filtered pages titled by the page alone, or by the category as if it were its archive.
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
			'favorites in a category' => array( 'pagename=favorites&pattern-categories=gallery', 'Favorites: Gallery &#8211; WordPress.org' ),
			'my patterns by status'   => array( 'pagename=my-patterns&status=pending', 'My Patterns: Pending &#8211; WordPress.org' ),
			'my patterns, both'       => array( 'pagename=my-patterns&pattern-categories=gallery&status=draft', 'My Patterns: Gallery, Draft &#8211; WordPress.org' ),
			'favorites, not filtered' => array( 'pagename=favorites', 'Favorites &#8211; WordPress.org' ),
		);
	}
}
