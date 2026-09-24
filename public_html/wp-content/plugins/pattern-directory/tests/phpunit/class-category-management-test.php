<?php
/**
 * Test who can manage the pattern-category taxonomy.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;
use WP_UnitTest_Factory;

/**
 * Authors assign categories to their own patterns (see Keyword_Assignment_Test), but the category list is
 * curated: only moderators create or edit the terms themselves.
 *
 * @group pattern-categories
 */
class Category_Management_Test extends WP_UnitTestCase {
	/**
	 * A moderator: holds `edit_others_patterns`.
	 *
	 * @var int
	 */
	protected static $moderator;

	/**
	 * A directory member with no moderator capability.
	 *
	 * @var int
	 */
	protected static $member;

	/**
	 * An existing category term.
	 *
	 * @var int
	 */
	protected static $category_term_id;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$moderator = $factory->user->create( array( 'role' => 'editor' ) );
		self::$member    = $factory->user->create( array( 'role' => 'subscriber' ) );

		self::$category_term_id = $factory->term->create(
			array(
				'taxonomy' => 'wporg-pattern-category',
				'name'     => 'Headers',
				'slug'     => 'header',
			)
		);
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		wp_delete_term( self::$category_term_id, 'wporg-pattern-category' );
		wp_delete_user( self::$moderator );
		wp_delete_user( self::$member );

		parent::tear_down_after_class();
	}

	/**
	 * Reset the current user between tests.
	 */
	public function tear_down(): void {
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Dispatch a request against the pattern-categories route.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Path below the route base.
	 * @param array  $params Request parameters: the JSON body for POST, query parameters otherwise.
	 * @return WP_REST_Response
	 */
	protected function request( string $method, string $path = '', array $params = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/wp/v2/pattern-categories' . $path );
		if ( 'POST' === $method && $params ) {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( wp_json_encode( $params ) );
		} elseif ( $params ) {
			$request->set_query_params( $params );
		}

		return rest_do_request( $request );
	}

	/**
	 * A member cannot add a category.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data
	 */
	public function test_member_cannot_create_category(): void {
		wp_set_current_user( self::$member );

		$response = $this->request( 'POST', '', array( 'name' => 'Member Category' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_cannot_create', $response->get_data()['code'] );
		$this->assertNull( term_exists( 'Member Category', 'wporg-pattern-category' ) );
	}

	/**
	 * A member cannot edit an existing category.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data
	 */
	public function test_member_cannot_edit_category(): void {
		wp_set_current_user( self::$member );

		$response = $this->request( 'POST', '/' . self::$category_term_id, array( 'name' => 'Changed' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_cannot_update', $response->get_data()['code'] );
		$this->assertSame( 'Headers', get_term( self::$category_term_id )->name );
	}

	/**
	 * A member cannot delete a category.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data
	 */
	public function test_member_cannot_delete_category(): void {
		wp_set_current_user( self::$member );

		$response = $this->request( 'DELETE', '/' . self::$category_term_id, array( 'force' => true ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_cannot_delete', $response->get_data()['code'] );
		$this->assertInstanceOf( \WP_Term::class, get_term( self::$category_term_id ) );
	}

	/**
	 * A member can still read the category list, which the pattern creator relies on.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data
	 */
	public function test_member_can_list_categories(): void {
		wp_set_current_user( self::$member );

		$response = $this->request( 'GET' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertContains( self::$category_term_id, wp_list_pluck( $response->get_data(), 'id' ) );
	}

	/**
	 * A moderator can add and edit categories.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data
	 */
	public function test_moderator_can_manage_categories(): void {
		wp_set_current_user( self::$moderator );

		$created = $this->request( 'POST', '', array( 'name' => 'Moderator Category' ) );
		$this->assertSame( 201, $created->get_status() );
		$term_id = $created->get_data()['id'];

		try {
			$updated = $this->request( 'POST', '/' . $term_id, array( 'name' => 'Renamed' ) );
			$this->assertSame( 200, $updated->get_status() );
			$this->assertSame( 'Renamed', get_term( $term_id )->name );
		} finally {
			wp_delete_term( $term_id, 'wporg-pattern-category' );
		}
	}
}
