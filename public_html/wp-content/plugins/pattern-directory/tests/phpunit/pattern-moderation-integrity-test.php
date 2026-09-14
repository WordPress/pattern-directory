<?php
/**
 * Test that a moderator's decision on a pattern survives what its author can do to it.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\TAX_TYPE as FLAG_REASON;

/**
 * Test moderation integrity.
 *
 * @group content-validation
 */
class Pattern_Moderation_Integrity_Test extends WP_UnitTestCase {
	/**
	 * The pattern's author: an ordinary account with no role, as on the wp.org network.
	 *
	 * @var int
	 */
	protected static $author;

	/**
	 * A directory moderator.
	 *
	 * @var int
	 */
	protected static $moderator;

	/**
	 * Set up the two accounts every test in this file compares.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$author    = $factory->user->create( array( 'role' => '' ) );
		self::$moderator = $factory->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * Create a pattern that a moderator has taken down.
	 *
	 * @param string $status The moderator-set status.
	 *
	 * @return int The pattern ID.
	 */
	protected function create_moderated_pattern( $status ) {
		$pattern_id = self::factory()->post->create( array(
			'post_type'   => POST_TYPE,
			'post_author' => self::$author,
			'post_status' => 'publish',
			'post_title'  => 'Taken down',
		) );

		wp_update_post( array(
			'ID'          => $pattern_id,
			'post_status' => $status,
		) );
		wp_set_object_terms( $pattern_id, 'spam', FLAG_REASON );

		return $pattern_id;
	}

	/**
	 * Send a REST request as a given user.
	 *
	 * @param int    $user   The user to act as.
	 * @param string $method The HTTP method.
	 * @param string $route  The REST route.
	 * @param array  $body   The request body.
	 *
	 * @return WP_REST_Response The response.
	 */
	protected function request_as( $user, $method, $route, $body = array() ) {
		wp_set_current_user( $user );

		$request = new WP_REST_Request( $method, $route );
		if ( $body ) {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
		}

		return rest_do_request( $request );
	}

	/**
	 * An author cannot trash a pattern a moderator removed, which is what let them untrash it back out.
	 *
	 * @dataProvider data_moderated_statuses
	 *
	 * @param string $status The moderator-set status.
	 */
	public function test_author_cannot_trash_a_moderated_pattern( $status ) {
		$pattern_id = $this->create_moderated_pattern( $status );

		$response = $this->request_as( self::$author, 'DELETE', '/wp/v2/wporg-pattern/' . $pattern_id );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_cannot_delete', $response->get_data()['code'] );
		$this->assertSame( $status, get_post_status( $pattern_id ) );
	}

	/**
	 * A moderator can still remove one.
	 *
	 * @dataProvider data_moderated_statuses
	 *
	 * @param string $status The moderator-set status.
	 */
	public function test_moderator_can_trash_a_moderated_pattern( $status ) {
		$pattern_id = $this->create_moderated_pattern( $status );

		$response = $this->request_as( self::$moderator, 'DELETE', '/wp/v2/wporg-pattern/' . $pattern_id );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'trash', get_post_status( $pattern_id ) );
	}

	/**
	 * Trashing is not a relist, so the reason a moderator recorded has to survive it.
	 */
	public function test_trashing_keeps_the_unlisted_reason() {
		$pattern_id = $this->create_moderated_pattern( UNLISTED_STATUS );

		wp_trash_post( $pattern_id );

		$this->assertCount( 1, wp_get_object_terms( $pattern_id, FLAG_REASON ) );
	}

	/**
	 * Relisting still clears it.
	 */
	public function test_relisting_clears_the_unlisted_reason() {
		$pattern_id = $this->create_moderated_pattern( UNLISTED_STATUS );

		wp_update_post( array(
			'ID'          => $pattern_id,
			'post_status' => 'publish',
		) );

		$this->assertCount( 0, wp_get_object_terms( $pattern_id, FLAG_REASON ) );
	}

	/**
	 * Restoring through a draft must not expose the old moderation reason after publication.
	 */
	public function test_restoring_and_republishing_clears_the_unlisted_reason(): void {
		$pattern_id = $this->create_moderated_pattern( UNLISTED_STATUS );

		wp_trash_post( $pattern_id );
		wp_untrash_post( $pattern_id );
		wp_update_post( array(
			'ID'          => $pattern_id,
			'post_status' => 'publish',
		) );
		wp_set_current_user( 0 );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/wporg-pattern/' . $pattern_id ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array(), $response->get_data()['unlisted_reason'] );
	}

	/**
	 * Restoring to the moderator's unlisted status must keep its reason.
	 */
	public function test_restoring_to_unlisted_keeps_the_reason(): void {
		$pattern_id = $this->create_moderated_pattern( UNLISTED_STATUS );

		wp_trash_post( $pattern_id );
		wp_update_post( array(
			'ID'          => $pattern_id,
			'post_status' => UNLISTED_STATUS,
		) );

		$this->assertCount( 1, wp_get_object_terms( $pattern_id, FLAG_REASON ) );
	}

	/**
	 * A trashed pattern still carries the moderator's status, so the status guard must read through it.
	 *
	 * @dataProvider data_moderated_statuses
	 *
	 * @param string $status The moderator-set status.
	 */
	public function test_author_cannot_publish_a_trashed_moderated_pattern( $status ) {
		$pattern_id = $this->create_moderated_pattern( $status );
		wp_trash_post( $pattern_id );

		foreach ( array( 'draft', 'publish' ) as $target ) {
			$response = $this->request_as( self::$author, 'POST', '/wp/v2/wporg-pattern/' . $pattern_id, array( 'status' => $target ) );

			$this->assertTrue( $response->is_error(), "Author reached `$target` from the trash." );
			$this->assertSame( 'rest_pattern_cannot_change_status', $response->get_data()['code'] );
		}

		$this->assertFalse( is_post_publicly_viewable( $pattern_id ) );
	}

	/**
	 * An ordinary pattern is still the author's to delete.
	 */
	public function test_author_can_still_trash_their_own_published_pattern() {
		$pattern_id = self::factory()->post->create( array(
			'post_type'   => POST_TYPE,
			'post_author' => self::$author,
			'post_status' => 'publish',
		) );

		$response = $this->request_as( self::$author, 'DELETE', '/wp/v2/wporg-pattern/' . $pattern_id );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'trash', get_post_status( $pattern_id ) );
	}

	/**
	 * The two statuses a moderator sets.
	 *
	 * @return array[]
	 */
	public function data_moderated_statuses() {
		return array(
			'unlisted' => array( UNLISTED_STATUS ),
			'spam'     => array( SPAM_STATUS ),
		);
	}
}
