<?php
/**
 * Test that a pattern's parent can only be set by a moderator.
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * `parent` links a translated pattern to its English original. Core accepts it over REST because the field is
 * in the schema and validates only that the id names an existing post, which let an author point their own
 * submission at any published pattern for the translation job to adopt.
 *
 * @group pattern-parent-validation
 */
class Pattern_Parent_Validation_Test extends WP_UnitTestCase {
	/**
	 * A moderator: holds `edit_others_patterns`.
	 *
	 * @var int
	 */
	protected static $moderator;

	/**
	 * The attacking pattern's author: a directory member with no moderator capability.
	 *
	 * @var int
	 */
	protected static $member;

	/**
	 * The member's own pattern, the one whose `parent` each test tries to set.
	 *
	 * @var int
	 */
	protected static $pattern_id;

	/**
	 * An unrelated published pattern by another author -- the adoption target.
	 *
	 * @var int
	 */
	protected static $victim_pattern_id;

	/**
	 * An ordinary post, to check a moderator's value is still type-checked.
	 *
	 * @var int
	 */
	protected static $unrelated_post_id;

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
				'post_title'  => 'A members own pattern',
				'post_status' => 'draft',
			)
		);

		self::$victim_pattern_id = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$moderator,
				'post_title'  => 'A published pattern by someone else',
				'post_status' => 'publish',
			)
		);

		self::$unrelated_post_id = $factory->post->create( array( 'post_type' => 'post' ) );
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		wp_delete_post( self::$pattern_id, true );
		wp_delete_post( self::$victim_pattern_id, true );
		wp_delete_post( self::$unrelated_post_id, true );
		wp_delete_user( self::$moderator );
		wp_delete_user( self::$member );

		parent::tear_down_after_class();
	}

	/**
	 * Detach any parent set by a test, and reset the current user.
	 */
	public function tear_down(): void {
		wp_update_post(
			array(
				'ID'          => self::$pattern_id,
				'post_parent' => 0,
			)
		);
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Dispatch a pattern update with the given body.
	 *
	 * @param array $body Request body.
	 * @return \WP_REST_Response
	 */
	protected function update_pattern( array $body ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wp/v2/' . POST_TYPE . '/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return rest_do_request( $request );
	}

	/**
	 * A member cannot point their own pattern at someone else's published pattern.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_parent
	 */
	public function test_member_cannot_set_parent(): void {
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'parent' => self::$victim_pattern_id ) );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_pattern_cannot_set_parent', $response->get_data()['code'] );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 0, get_post( self::$pattern_id )->post_parent );
	}

	/**
	 * A moderator can set the parent, which is how a genuine translation is linked.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_parent
	 */
	public function test_moderator_can_set_parent(): void {
		wp_set_current_user( self::$moderator );

		$response = $this->update_pattern( array( 'parent' => self::$victim_pattern_id ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( self::$victim_pattern_id, get_post( self::$pattern_id )->post_parent );
	}

	/**
	 * Even a moderator's value has to name another pattern, not an arbitrary post.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_parent
	 */
	public function test_moderator_cannot_set_a_non_pattern_parent(): void {
		wp_set_current_user( self::$moderator );

		$response = $this->update_pattern( array( 'parent' => self::$unrelated_post_id ) );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_pattern_invalid_parent', $response->get_data()['code'] );
		$this->assertSame( 0, get_post( self::$pattern_id )->post_parent );
	}

	/**
	 * Echoing back the stored value is not a write, so an ordinary round-trip edit still succeeds.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_parent
	 */
	public function test_member_can_resend_the_unchanged_parent(): void {
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'parent' => 0 ) );

		$this->assertFalse( $response->is_error() );
	}

	/**
	 * An update that doesn't mention `parent` is untouched by the check.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_parent
	 */
	public function test_update_without_parent_is_unaffected(): void {
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'title' => 'A renamed pattern' ) );

		$this->assertFalse( $response->is_error() );
	}
}
