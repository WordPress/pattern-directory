<?php

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Test pattern validation.
 *
 * @group pattern-directory-capabilities
 */
class Capabilities_Test extends WP_UnitTestCase {
	protected static $user_admin;
	protected static $user_editor;
	protected static $user_author;
	protected static $user_contributor;
	protected static $user_subscriber;
	protected static $user_not_on_site;
	protected static $pattern_id_1;
	protected static $pattern_id_2;
	protected static $post_id;

	/**
	 * For the purposes of these tests, admins and editors have the same capabilities.
	 */
	protected $admin_editor_caps = array(
		'delete_others_patterns', 'delete_patterns', 'delete_private_patterns', 'delete_published_patterns',
		'edit_others_patterns', 'edit_patterns', 'edit_private_patterns', 'edit_published_patterns',
		'publish_patterns', 'read_private_patterns',
	);

	protected $author_caps = array(
		'delete_patterns', 'delete_published_patterns',
		'edit_patterns', 'edit_published_patterns',
		'publish_patterns',
	);

	protected $contributor_caps = array(
		'delete_patterns',
		'edit_patterns',
	);

	protected $subscriber_caps = array();

	protected $granted_caps = array(
		'edit_patterns'             => true,
		'edit_published_patterns'   => true,
		'publish_patterns'          => true,
		'delete_patterns'           => true,
		'delete_published_patterns' => true,
	);

	/**
	 * Set up shared fixtures.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user_admin = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		self::$user_editor = $factory->user->create(
			array(
				'role' => 'editor',
			)
		);
		self::$user_author = $factory->user->create(
			array(
				'role' => 'author',
			)
		);
		self::$user_contributor = $factory->user->create(
			array(
				'role' => 'contributor',
			)
		);
		self::$user_subscriber = $factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);

		// Create a user, then remove it from the current site.
		self::$user_not_on_site = $factory->user->create(
			array(
				'role' => 'author',
			)
		);
		global $current_site;
		remove_user_from_blog( self::$user_not_on_site, $current_site->id );

		self::$pattern_id_1 = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$user_editor,
			)
		);
		self::$pattern_id_2 = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$user_not_on_site,
			)
		);
		self::$post_id = $factory->post->create();
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class() {
		wp_delete_post( self::$pattern_id_1, true );
		wp_delete_post( self::$pattern_id_2, true );
		wp_delete_post( self::$post_id, true );

		wp_delete_user( self::$user_admin );
		wp_delete_user( self::$user_editor );
		wp_delete_user( self::$user_author );
		wp_delete_user( self::$user_contributor );
		wp_delete_user( self::$user_subscriber );
		wp_delete_user( self::$user_not_on_site );
	}

	/**
	 * Reset state between tests.
	 */
	public function tear_down() {
		unset( $GLOBALS['current_screen'] );
		wp_set_current_user( 0 );
	}

	/**
	 * Simulate the back end environment. Causes is_admin() to return true.
	 */
	protected function set_admin_screen() {
		require_once ABSPATH . '/wp-admin/includes/class-wp-screen.php';
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['current_screen'] = \WP_Screen::get( POST_TYPE );
	}

	/**
	 * Build an array of capabilities and their expected results from capability checks.
	 *
	 * @param array $yes_caps
	 * @param array $all_caps
	 *
	 * @return array
	 */
	protected function merge_caps( $yes_caps, $all_caps ) {
		return array_merge(
			array_fill_keys( $all_caps, false ),
			array_fill_keys( $yes_caps, true )
		);
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_pattern_caps_admin() {
		wp_set_current_user( self::$user_admin );

		$caps = $this->merge_caps( $this->admin_editor_caps, $this->admin_editor_caps );

		// Test front end caps.
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on front end for %s', $cap )
			);
		}

		// Test back end caps.
		$this->set_admin_screen();
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on back end for %s', $cap )
			);
		}
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_pattern_caps_editor() {
		wp_set_current_user( self::$user_editor );

		$caps = $this->merge_caps( $this->admin_editor_caps, $this->admin_editor_caps );

		// Test front end caps.
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on front end for %s', $cap )
			);
		}

		// Test back end caps.
		$this->set_admin_screen();
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on back end for %s', $cap )
			);
		}
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_pattern_caps_author() {
		wp_set_current_user( self::$user_author );

		$caps = $this->merge_caps( $this->author_caps, $this->admin_editor_caps );

		// Test front end caps.
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on front end for %s', $cap )
			);
		}

		// Test back end caps.
		$this->set_admin_screen();
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on back end for %s', $cap )
			);
		}
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_pattern_caps_contributor() {
		wp_set_current_user( self::$user_contributor );

		$caps = $this->merge_caps( $this->contributor_caps, $this->admin_editor_caps );
		$front_end_caps = array_merge( $caps, $this->granted_caps );

		// Test front end caps.
		foreach ( $front_end_caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on front end for %s', $cap )
			);
		}

		// Test back end caps.
		$this->set_admin_screen();
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on back end for %s', $cap )
			);
		}
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_pattern_caps_subscriber() {
		wp_set_current_user( self::$user_subscriber );

		$caps = $this->merge_caps( $this->subscriber_caps, $this->admin_editor_caps );
		$front_end_caps = array_merge( $caps, $this->granted_caps );

		// Test front end caps.
		foreach ( $front_end_caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on front end for %s', $cap )
			);
		}

		// Test back end caps.
		$this->set_admin_screen();
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on back end for %s', $cap )
			);
		}
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_pattern_caps_logged_out_user() {
		$caps = $this->merge_caps( array(), $this->admin_editor_caps );

		// Test front end caps.
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on front end for %s', $cap )
			);
		}

		// Test back end caps.
		$this->set_admin_screen();
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on back end for %s', $cap )
			);
		}
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_pattern_caps_user_not_on_site() {
		wp_set_current_user( self::$user_not_on_site );

		$caps = $this->merge_caps( array(), $this->admin_editor_caps );
		$front_end_caps = array_merge( $caps, $this->granted_caps );

		// Test front end caps.
		foreach ( $front_end_caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on front end for %s', $cap )
			);
		}
		$this->assertTrue( current_user_can( 'read' ), 'Failed on front end for read' );

		// Test back end caps.
		$this->set_admin_screen();
		foreach ( $caps as $cap => $expected_result ) {
			$this->assertEquals(
				$expected_result,
				current_user_can( $cap ),
				sprintf( 'Failed on back end for %s', $cap )
			);
		}
		$this->assertFalse( current_user_can( 'read' ), 'Failed on back end for read' );
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_user_not_on_site_cant_edit_other_pattern() {
		wp_set_current_user( self::$user_not_on_site );

		// Test front end.
		$this->assertFalse(
			current_user_can( 'edit_post', self::$pattern_id_1 ),
			'Failed on front end'
		);

		// Test back end.
		$this->set_admin_screen();
		$this->assertFalse(
			current_user_can( 'edit_post', self::$pattern_id_1 ),
			'Failed on back end'
		);
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_user_not_on_site_can_edit_own_pattern() {
		wp_set_current_user( self::$user_not_on_site );

		// Test front end.
		$this->assertTrue(
			current_user_can( 'edit_post', self::$pattern_id_2 ),
			'Failed on front end'
		);

		// Test back end.
		$this->set_admin_screen();
		$this->assertFalse(
			current_user_can( 'edit_post', self::$pattern_id_2 ),
			'Failed on back end'
		);
	}

	/**
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\set_pattern_caps()
	 */
	public function test_user_not_on_site_cant_edit_other_post_type() {
		wp_set_current_user( self::$user_not_on_site );

		// Test front end.
		$this->assertFalse(
			current_user_can( 'edit_post', self::$post_id ),
			'Failed on front end'
		);

		// Test back end.
		$this->set_admin_screen();
		$this->assertFalse(
			current_user_can( 'edit_post', self::$post_id ),
			'Failed on back end'
		);
	}
}
