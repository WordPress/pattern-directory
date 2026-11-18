<?php
/**
 * Test the author archive heading.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_HTML_Tag_Processor;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use function WordPressdotorg\Theme\Pattern_Directory_2024\Block_Config\update_archive_title;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

require_once __DIR__ . '/trait-loads-theme.php';

/**
 * Author archives show a heading that links to the author's WordPress.org profile (#696).
 */
class Theme_Author_Profile_Link_Test extends WP_UnitTestCase {
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
		self::$author = $factory->user->create_and_get(
			array(
				'user_nicename' => 'pattern-author',
				'display_name'  => 'Pattern Author',
			)
		);
		foreach ( array( POST_TYPE, 'post' ) as $post_type ) {
			$factory->post->create(
				array(
					'post_type'   => $post_type,
					'post_author' => self::$author->ID,
					'post_status' => 'publish',
				)
			);
		}

		self::load_theme();
	}

	/**
	 * Use pretty permalinks, as the site does.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->set_permalink_structure( '/%postname%/' );
	}

	/**
	 * Render a filter-type query title block through the theme.
	 *
	 * @param array $attrs Block attributes.
	 * @return WP_HTML_Tag_Processor A processor positioned before the output.
	 */
	protected function render_title( array $attrs ): WP_HTML_Tag_Processor {
		$block = array(
			'blockName'    => 'core/query-title',
			'attrs'        => array_merge( array( 'type' => 'filter' ), $attrs ),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);

		return new WP_HTML_Tag_Processor( update_archive_title( '', $block ) );
	}

	/**
	 * Catches an author archive whose grid heading is hidden or has no link to the profile.
	 */
	public function test_grid_title_links_to_profile(): void {
		$this->go_to( get_author_posts_url( self::$author->ID ) );
		$this->assertTrue( is_author() );

		$tags = $this->render_title(
			array(
				'level'     => 1,
				'className' => 'screen-reader-text',
			)
		);

		$this->assertTrue( $tags->next_tag( 'h1' ) );
		$this->assertFalse( $tags->has_class( 'screen-reader-text' ) );
		$this->assertTrue( $tags->next_tag( 'a' ) );
		$this->assertSame( 'https://profiles.wordpress.org/pattern-author/', $tags->get_attribute( 'href' ) );
	}

	/**
	 * Catches the profile link leaking into the local navigation bar's title.
	 */
	public function test_navigation_title_stays_plain(): void {
		$this->go_to( get_author_posts_url( self::$author->ID ) );

		$tags = $this->render_title( array( 'className' => 'wporg-local-navigation-bar__fade-in-scroll' ) );

		$this->assertTrue( $tags->next_tag( 'h1' ) );
		$this->assertFalse( $tags->next_tag( 'a' ) );
	}

	/**
	 * Catches the heading showing on archives that are not by an author.
	 */
	public function test_front_page_title_stays_hidden(): void {
		$this->go_to( home_url( '/' ) );

		$tags = $this->render_title(
			array(
				'level'     => 1,
				'className' => 'screen-reader-text',
			)
		);

		$this->assertTrue( $tags->next_tag( 'h1' ) );
		$this->assertTrue( $tags->has_class( 'screen-reader-text' ) );
		$this->assertFalse( $tags->next_tag( 'a' ) );
	}
}
