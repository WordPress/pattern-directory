<?php
/**
 * Test which pattern categories authors can assign.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Authors can only add the selectable categories, but keep any retired ones already on their pattern.
 * Moderators can assign any category.
 *
 * @group pattern-categories
 */
class Category_Assignment_Test extends WP_UnitTestCase {
	/**
	 * A moderator: holds `edit_others_patterns`.
	 *
	 * @var int
	 */
	protected static $moderator;

	/**
	 * The pattern's author: a directory member with no moderator capability.
	 *
	 * @var int
	 */
	protected static $member;

	/**
	 * The member's own pattern.
	 *
	 * @var int
	 */
	protected static $pattern_id;

	/**
	 * A selectable category term.
	 *
	 * @var int
	 */
	protected static $selectable_term_id;

	/**
	 * A retired category term.
	 *
	 * @var int
	 */
	protected static $retired_term_id;

	/**
	 * The "Featured" category term.
	 *
	 * @var int
	 */
	protected static $featured_term_id;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$moderator = $factory->user->create( array( 'role' => 'editor' ) );
		self::$member    = $factory->user->create( array( 'role' => 'subscriber' ) );

		self::$pattern_id = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$member,
				'post_status' => 'draft',
			)
		);

		self::$selectable_term_id = $factory->term->create(
			array(
				'taxonomy' => 'wporg-pattern-category',
				'name'     => 'Headers',
				'slug'     => 'header',
			)
		);
		self::$retired_term_id    = $factory->term->create(
			array(
				'taxonomy' => 'wporg-pattern-category',
				'name'     => 'Columns',
				'slug'     => 'columns',
			)
		);
		self::$featured_term_id   = $factory->term->create(
			array(
				'taxonomy' => 'wporg-pattern-category',
				'name'     => 'Featured',
				'slug'     => 'featured',
			)
		);
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		wp_delete_post( self::$pattern_id, true );
		wp_delete_term( self::$selectable_term_id, 'wporg-pattern-category' );
		wp_delete_term( self::$retired_term_id, 'wporg-pattern-category' );
		wp_delete_term( self::$featured_term_id, 'wporg-pattern-category' );
		wp_delete_user( self::$moderator );
		wp_delete_user( self::$member );

		parent::tear_down_after_class();
	}

	/**
	 * Detach any assigned terms and reset the current user between tests.
	 */
	public function tear_down(): void {
		wp_delete_object_term_relationships( self::$pattern_id, 'wporg-pattern-category' );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Dispatch a pattern update setting the given categories.
	 *
	 * @param int[] $term_ids Category term IDs.
	 * @return \WP_REST_Response
	 */
	protected function set_categories( array $term_ids ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wp/v2/' . POST_TYPE . '/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'pattern-categories' => $term_ids ) ) );

		return rest_do_request( $request );
	}

	/**
	 * The category term IDs currently on the pattern.
	 *
	 * @return int[]
	 */
	protected function get_pattern_categories(): array {
		$term_ids = wp_get_object_terms( self::$pattern_id, 'wporg-pattern-category', array( 'fields' => 'ids' ) );
		sort( $term_ids );

		return $term_ids;
	}

	/**
	 * A member can assign a selectable category.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_categories
	 */
	public function test_member_can_assign_selectable_category(): void {
		wp_set_current_user( self::$member );

		$response = $this->set_categories( array( self::$selectable_term_id ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( array( self::$selectable_term_id ), $this->get_pattern_categories() );
	}

	/**
	 * A member cannot add a retired category or "Featured".
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_categories
	 */
	public function test_member_cannot_add_unselectable_category(): void {
		wp_set_current_user( self::$member );

		foreach ( array( self::$retired_term_id, self::$featured_term_id ) as $term_id ) {
			$response = $this->set_categories( array( self::$selectable_term_id, $term_id ) );

			$this->assertTrue( $response->is_error() );
			$this->assertSame( 'rest_pattern_invalid_category', $response->get_data()['code'] );
			$this->assertSame( array(), $this->get_pattern_categories() );
		}
	}

	/**
	 * A member can re-send categories already on their pattern, even retired ones, and can remove them.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_categories
	 */
	public function test_member_keeps_existing_unselectable_categories(): void {
		wp_set_object_terms( self::$pattern_id, array( self::$retired_term_id, self::$featured_term_id ), 'wporg-pattern-category' );
		wp_set_current_user( self::$member );

		$expected = array( self::$selectable_term_id, self::$retired_term_id, self::$featured_term_id );
		sort( $expected );
		$response = $this->set_categories( $expected );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( $expected, $this->get_pattern_categories() );

		$response = $this->set_categories( array( self::$selectable_term_id ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( array( self::$selectable_term_id ), $this->get_pattern_categories() );
	}

	/**
	 * A moderator can assign any category.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_categories
	 */
	public function test_moderator_can_assign_any_category(): void {
		wp_set_current_user( self::$moderator );

		$expected = array( self::$retired_term_id, self::$featured_term_id );
		sort( $expected );
		$response = $this->set_categories( $expected );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( $expected, $this->get_pattern_categories() );
	}
}
