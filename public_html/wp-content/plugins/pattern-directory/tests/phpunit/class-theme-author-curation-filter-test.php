<?php
/**
 * Test the curation filter on author archives.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

require_once __DIR__ . '/trait-loads-theme.php';

/**
 * Author archives leave out the curation filter, whose "Curated" option 404s there (#706).
 */
class Theme_Author_Curation_Filter_Test extends WP_UnitTestCase {
	use Loads_Theme;

	/**
	 * The pattern author.
	 *
	 * @var \WP_User
	 */
	protected static $author;

	/**
	 * Create an author with a pattern, and load the theme's functions.
	 *
	 * @param WP_UnitTest_Factory $factory Test data factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$author = $factory->user->create_and_get( array( 'user_nicename' => 'pattern-author' ) );
		$factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$author->ID,
				'post_status' => 'publish',
			)
		);

		self::load_theme();
	}

	/**
	 * Register just the theme hook under test.
	 */
	public function set_up(): void {
		parent::set_up();

		add_filter( 'render_block_wporg/query-filter', 'WordPressdotorg\Theme\Pattern_Directory_2024\Block_Config\hide_curation_filter_on_author_archives', 10, 2 );
	}

	/**
	 * Run a query-filter block's output through the theme's render filter.
	 *
	 * @param string $key The filter key.
	 * @return string The filtered block content.
	 */
	protected function render_filter( string $key ): string {
		$block = array(
			'blockName' => 'wporg/query-filter',
			'attrs'     => array( 'key' => $key ),
		);

		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- core's render_block_{$name} hook.
		return apply_filters( 'render_block_wporg/query-filter', '<div class="wporg-query-filter">' . $key . '</div>', $block );
	}

	/**
	 * Catches an author archive that still offers the curation filter.
	 */
	public function test_author_archive_hides_curation_filter(): void {
		$this->go_to( get_author_posts_url( self::$author->ID ) );
		$this->assertTrue( is_author() );

		$this->assertSame( '', $this->render_filter( 'curation' ) );
	}

	/**
	 * Catches an author archive that loses its other filters too.
	 */
	public function test_author_archive_keeps_sort_filter(): void {
		$this->go_to( get_author_posts_url( self::$author->ID ) );

		$this->assertStringContainsString( 'sort', $this->render_filter( 'sort' ) );
	}

	/**
	 * Catches the curation filter going missing from other archives.
	 */
	public function test_front_page_keeps_curation_filter(): void {
		$this->go_to( home_url( '/' ) );

		$this->assertStringContainsString( 'curation', $this->render_filter( 'curation' ) );
	}
}
