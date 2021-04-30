<?php
/**
 * Test Block Pattern validation.
 */

use function WordPressdotorg\Pattern_Directory\Favorite\{save_favorite, get_favorites};
use const WordPressdotorg\Pattern_Directory\Favorite\META_KEY;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Test pattern validation.
 */
class Pattern_Favorite_Test extends WP_UnitTestCase {
	protected static $pattern_id;
	protected static $faved_pattern_id;
	protected static $page_id;
	protected static $user_admin;
	protected static $user_subscriber;

	/**
	 * Setup fixtures that are shared across all tests.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$pattern_id = $factory->post->create(
			array( 'post_type' => POST_TYPE )
		);
		self::$faved_pattern_id = $factory->post->create(
			array( 'post_type' => POST_TYPE )
		);
		self::$page_id = $factory->post->create(
			array( 'post_type' => 'page' )
		);
		self::$user_admin = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		self::$user_subscriber = $factory->user->create(
			array(
				'role' => 'subscriber',
			)
		);
		update_user_meta( self::$user_admin, META_KEY, array( self::$faved_pattern_id ) );
	}

	/**
	 * Test favoriting a pattern as the admin user.
	 */
	public function test_favorite_pattern() {
		wp_set_current_user( self::$user_admin );
		$success = save_favorite( self::$pattern_id );
		$this->assertTrue( (bool) $success );

		$favorites = get_user_meta( self::$user_admin, META_KEY, true );
		$this->assertSame( $favorites, array( self::$faved_pattern_id, self::$pattern_id ) );
	}

	/**
	 * Test unfavoriting a pattern as the admin user.
	 */
	public function test_unfavorite_faved_pattern() {
		wp_set_current_user( self::$user_admin );
		$success = save_favorite( self::$faved_pattern_id, null, false );
		$this->assertTrue( (bool) $success );

		$favorites = get_user_meta( self::$user_admin, META_KEY, true );
		$this->assertSame( $favorites, array() );
	}

	/**
	 * Test favoriting an already favorited pattern.
	 */
	public function test_favorite_faved_pattern() {
		wp_set_current_user( self::$user_admin );
		$success = save_favorite( self::$faved_pattern_id );
		$this->assertTrue( (bool) $success );

		$favorites = get_user_meta( self::$user_admin, META_KEY, true );
		$this->assertSame( $favorites, array( self::$faved_pattern_id ) );
	}

	/**
	 * Test unfavoriting an non-favorited pattern.
	 */
	public function test_unfavorite_nonfaved_pattern() {
		wp_set_current_user( self::$user_admin );
		$success = save_favorite( self::$pattern_id, null, false );
		$this->assertTrue( (bool) $success );

		$favorites = get_user_meta( self::$user_admin, META_KEY, true );
		$this->assertSame( $favorites, array( self::$faved_pattern_id ) );
	}

	/**
	 * Test favoriting an invalid pattern (not allowed).
	 */
	public function test_favorite_invalid_pattern() {
		wp_set_current_user( self::$user_admin );
		$success = save_favorite( 'invalid-id' );
		$this->assertFalse( (bool) $success );
	}

	/**
	 * Test favoriting a page (not allowed).
	 */
	public function test_favorite_page() {
		wp_set_current_user( self::$user_admin );
		$success = save_favorite( self::$page_id );
		$this->assertFalse( (bool) $success );
	}

	/**
	 * Test favoriting a pattern as a subscriber.
	 */
	public function test_favorite_pattern_subscriber() {
		wp_set_current_user( self::$user_subscriber );
		$success = save_favorite( self::$pattern_id );
		$this->assertTrue( (bool) $success );

		$favorites = get_user_meta( self::$user_subscriber, META_KEY, true );
		$this->assertSame( $favorites, array( self::$pattern_id ) );
	}

	/**
	 * Test favoriting a pattern as an anonymous (not logged in) user.
	 */
	public function test_favorite_pattern_anon() {
		$success = save_favorite( self::$pattern_id );
		$this->assertFalse( (bool) $success );
	}

	/**
	 * Test favoriting a pattern as an anonymous user, for a real user.
	 * This would only be done from the server, so there isn't a security issue here.
	 */
	public function test_anon_favorite_pattern_admin() {
		$success = save_favorite( self::$pattern_id, self::$user_admin );
		$this->assertTrue( (bool) $success );

		$favorites = get_user_meta( self::$user_admin, META_KEY, true );
		$this->assertSame( $favorites, array( self::$faved_pattern_id, self::$pattern_id ) );
	}

	/**
	 * Test getting favorite patterns when a user has some.
	 */
	public function test_get_favorite_patterns() {
		wp_set_current_user( self::$user_admin );
		$favorites = get_favorites();
		$this->assertSame( $favorites, array( self::$faved_pattern_id ) );
	}

	/**
	 * Test getting favorite patterns when a user has none.
	 */
	public function test_get_favorite_patterns_subscriber() {
		$favorites = get_favorites( self::$user_subscriber );
		$this->assertEmpty( $favorites );
	}
}
