<?php
/**
 * Test access control on assigning the internal pattern-keyword taxonomy.
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * The `wporg-pattern-keyword` taxonomy is internal: its `core` term feeds WordPress Core's remote pattern
 * distribution, so only moderators may assign it. Categories stay author-assignable.
 *
 * @group pattern-keywords
 */
class Keyword_Assignment_Test extends WP_UnitTestCase {
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
	 * The privileged `core` keyword term.
	 *
	 * @var int
	 */
	protected static $core_term_id;

	/**
	 * An ordinary, author-assignable category term.
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

		self::$pattern_id = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$member,
				'post_status' => 'draft',
			)
		);

		self::$core_term_id     = $factory->term->create(
			array(
				'taxonomy' => 'wporg-pattern-keyword',
				'name'     => 'core',
				'slug'     => 'core',
			)
		);
		self::$category_term_id = $factory->term->create(
			array(
				'taxonomy' => 'wporg-pattern-category',
				'name'     => 'Headers',
			)
		);
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		wp_delete_post( self::$pattern_id, true );
		wp_delete_term( self::$core_term_id, 'wporg-pattern-keyword' );
		wp_delete_term( self::$category_term_id, 'wporg-pattern-category' );
		wp_delete_user( self::$moderator );
		wp_delete_user( self::$member );

		parent::tear_down_after_class();
	}

	/**
	 * Detach any assigned terms and reset the current user between tests.
	 */
	public function tear_down(): void {
		wp_delete_object_term_relationships(
			self::$pattern_id,
			array( 'wporg-pattern-keyword', 'wporg-pattern-category' )
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
	 * Whether the pattern currently carries the given term.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy.
	 * @return bool
	 */
	protected function pattern_has_term( int $term_id, string $taxonomy ): bool {
		$term_ids = wp_get_object_terms( self::$pattern_id, $taxonomy, array( 'fields' => 'ids' ) );

		return in_array( $term_id, $term_ids, true );
	}

	/**
	 * A member cannot assign the `core` keyword to their own pattern.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data
	 */
	public function test_member_cannot_assign_core_keyword(): void {
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'pattern-keywords' => array( self::$core_term_id ) ) );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_cannot_assign_term', $response->get_data()['code'] );
		$this->assertFalse( $this->pattern_has_term( self::$core_term_id, 'wporg-pattern-keyword' ) );
	}

	/**
	 * A moderator can assign the `core` keyword.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data
	 */
	public function test_moderator_can_assign_core_keyword(): void {
		wp_set_current_user( self::$moderator );

		$response = $this->update_pattern( array( 'pattern-keywords' => array( self::$core_term_id ) ) );

		$this->assertFalse( $response->is_error() );
		$this->assertTrue( $this->pattern_has_term( self::$core_term_id, 'wporg-pattern-keyword' ) );
	}

	/**
	 * A member can still assign an ordinary category to their own pattern.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data
	 */
	public function test_member_can_assign_category(): void {
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'pattern-categories' => array( self::$category_term_id ) ) );

		$this->assertFalse( $response->is_error() );
		$this->assertTrue( $this->pattern_has_term( self::$category_term_id, 'wporg-pattern-category' ) );
	}
}
