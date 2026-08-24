<?php
/**
 * Test access control on the pattern-flag REST routes.
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\POST_TYPE as FLAG_POST_TYPE;

/**
 * Pattern flags are abuse reports, carrying who reported a pattern and why; only moderators may read them.
 * `edit_patterns` is granted to every logged-in user by `set_pattern_caps()` and `edit_post` on the parent is
 * true for the pattern's own author, so both routes must gate on `edit_others_patterns` instead.
 *
 * @group pattern-flags
 */
class Flag_Permissions_Test extends WP_UnitTestCase {
	/**
	 * A moderator: holds `edit_others_patterns`.
	 *
	 * @var int
	 */
	protected static $moderator;

	/**
	 * A directory member: gets `edit_patterns` via `set_pattern_caps()`, but not `edit_others_patterns`.
	 *
	 * @var int
	 */
	protected static $member;

	/**
	 * A published pattern that the flag is filed against.
	 *
	 * @var int
	 */
	protected static $pattern_id;

	/**
	 * A pending abuse report filed against the pattern.
	 *
	 * @var int
	 */
	protected static $flag_id;

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
				'post_status' => 'publish',
			)
		);

		self::$flag_id = $factory->post->create(
			array(
				'post_type'    => FLAG_POST_TYPE,
				'post_status'  => 'pending',
				'post_parent'  => self::$pattern_id,
				'post_excerpt' => 'PRIVATE-REPORT-CANARY',
			)
		);
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		wp_delete_post( self::$flag_id, true );
		wp_delete_post( self::$pattern_id, true );
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
	 * Dispatch a GET against the flag collection with the given query string.
	 *
	 * @param string $query Optional query string, without the leading `?`.
	 * @return \WP_REST_Response
	 */
	protected function list_flags( string $query = '' ): \WP_REST_Response {
		$route   = '/wp/v2/' . FLAG_POST_TYPE;
		$request = new WP_REST_Request( 'GET', $route );
		if ( $query ) {
			$request->set_query_params( wp_parse_args( $query ) );
		}

		return rest_do_request( $request );
	}

	/**
	 * A member without `edit_others_patterns` cannot list flags.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\REST_Flags_Controller::get_items_permissions_check
	 */
	public function test_member_cannot_list_flags(): void {
		wp_set_current_user( self::$member );

		$response = $this->list_flags();

		$this->assertTrue( $response->is_error(), 'A member should not be able to list flags.' );
		$this->assertSame( 'rest_forbidden_context', $response->get_data()['code'] );
	}

	/**
	 * A logged-out request cannot list flags.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\REST_Flags_Controller::get_items_permissions_check
	 */
	public function test_logged_out_cannot_list_flags(): void {
		$response = $this->list_flags();

		$this->assertTrue( $response->is_error(), 'A logged-out request should not be able to list flags.' );
		$this->assertSame( 'rest_forbidden_context', $response->get_data()['code'] );
	}

	/**
	 * The count header is not leaked to a member: the request is refused before any total is computed.
	 *
	 * This closes the `X-WP-Total` oracle that let a member probe the hidden moderation queue with
	 * `?parent=`, `?author=`, `?search=`, etc.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\REST_Flags_Controller::get_items_permissions_check
	 */
	public function test_member_cannot_probe_flag_count(): void {
		wp_set_current_user( self::$member );

		$response = $this->list_flags( 'parent[]=' . self::$pattern_id );

		$this->assertTrue( $response->is_error(), 'A member should not be able to probe the flag count.' );
		$this->assertArrayNotHasKey( 'X-WP-Total', $response->get_headers() );
	}

	/**
	 * A moderator can list flags.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\REST_Flags_Controller::get_items_permissions_check
	 */
	public function test_moderator_can_list_flags(): void {
		wp_set_current_user( self::$moderator );

		$response = $this->list_flags();

		$this->assertFalse( $response->is_error(), 'A moderator should be able to list flags.' );
		$this->assertSame( array( self::$flag_id ), wp_list_pluck( $response->get_data(), 'id' ) );
	}

	/**
	 * Dispatch a GET for the single flag.
	 *
	 * @return \WP_REST_Response
	 */
	protected function read_flag(): \WP_REST_Response {
		$request = new WP_REST_Request( 'GET', '/wp/v2/' . FLAG_POST_TYPE . '/' . self::$flag_id );

		return rest_do_request( $request );
	}

	/**
	 * The reported pattern's own author cannot read the report filed against it.
	 *
	 * `edit_post` on the parent is true for its author, so the single-item route used to admit them; core's
	 * `check_read_permission()` denied the row only because the flag post type falls back to the generic
	 * `post` caps, which is incidental rather than intentional.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\REST_Flags_Controller::get_item_permissions_check
	 */
	public function test_pattern_author_cannot_read_flag_on_own_pattern(): void {
		wp_set_current_user( self::$member );

		$response = $this->read_flag();

		$this->assertTrue( $response->is_error(), 'A pattern author should not be able to read its flags.' );
		$this->assertSame( 'rest_cannot_read', $response->get_data()['code'] );
		$this->assertStringNotContainsString( 'PRIVATE-REPORT-CANARY', wp_json_encode( $response->get_data() ) );
	}

	/**
	 * A logged-out request cannot read a flag.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\REST_Flags_Controller::get_item_permissions_check
	 */
	public function test_logged_out_cannot_read_flag(): void {
		$response = $this->read_flag();

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_cannot_read', $response->get_data()['code'] );
	}

	/**
	 * A moderator can read the flag, including the reporter's free text.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\REST_Flags_Controller::get_item_permissions_check
	 */
	public function test_moderator_can_read_flag(): void {
		wp_set_current_user( self::$moderator );

		$response = $this->read_flag();

		$this->assertFalse( $response->is_error(), 'A moderator should be able to read a flag.' );
		$this->assertSame( self::$flag_id, $response->get_data()['id'] );
	}
}
