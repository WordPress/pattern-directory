<?php
/**
 * Test the automatic removal of a pattern that collects enough reports.
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ POST_TYPE as FLAG_POST_TYPE, TAX_TYPE as FLAG_REASON };

/**
 * Test report thresholds, moderator approval, and author permissions.
 *
 * @group pattern-flags
 */
class Flag_Threshold_Test extends WP_UnitTestCase {
	/**
	 * The pattern's author: a directory member with no moderator capability.
	 *
	 * @var int
	 */
	protected static $author;

	/**
	 * A moderator: holds `edit_others_patterns`.
	 *
	 * @var int
	 */
	protected static $moderator;

	/**
	 * A user reporting the pattern.
	 *
	 * @var int
	 */
	protected static $reporter;

	/**
	 * A second, unrelated reporter.
	 *
	 * @var int
	 */
	protected static $other_reporter;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$author         = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$moderator      = $factory->user->create( array( 'role' => 'editor' ) );
		self::$reporter       = $factory->user->create( array( 'role' => 'subscriber' ) );
		self::$other_reporter = $factory->user->create( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		wp_delete_user( self::$author );
		wp_delete_user( self::$moderator );
		wp_delete_user( self::$reporter );
		wp_delete_user( self::$other_reporter );

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
	 * Create a pattern owned by the author.
	 *
	 * @param string $status Optional. The pattern's status. Default 'publish'.
	 *
	 * @return int The new pattern ID.
	 */
	protected function create_pattern( string $status = 'publish' ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$author,
				'post_status' => $status,
			)
		);
	}

	/**
	 * Create a pending report against a pattern.
	 *
	 * @param int $pattern_id The reported pattern.
	 * @param int $reporter   The reporting user.
	 *
	 * @return int The new flag ID.
	 */
	protected function create_flag( int $pattern_id, int $reporter ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => FLAG_POST_TYPE,
				'post_parent' => $pattern_id,
				'post_author' => $reporter,
				'post_status' => 'pending',
			)
		);
	}

	/**
	 * Ask the REST API to set a pattern's status, as the current user.
	 *
	 * @param int    $pattern_id The pattern to update.
	 * @param string $status     The status to set.
	 *
	 * @return \WP_REST_Response The response to the request.
	 */
	protected function set_pattern_status( int $pattern_id, string $status ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wp/v2/' . POST_TYPE . '/' . $pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'status' => $status ) ) );

		return rest_do_request( $request );
	}

	/**
	 * Ask the REST API to publish a pattern, as the current user.
	 *
	 * @param int $pattern_id The pattern to publish.
	 *
	 * @return \WP_REST_Response The response to the request.
	 */
	protected function publish_pattern( int $pattern_id ): \WP_REST_Response {
		return $this->set_pattern_status( $pattern_id, 'publish' );
	}

	/**
	 * Create a legacy removal: a pending pattern with reports at the threshold.
	 *
	 * @return int The pattern ID.
	 */
	protected function create_legacy_removal(): int {
		update_option( 'wporg-pattern-flag_threshold', 2 );

		// Reports against a draft don't trigger the removal, so the status can then be set by hand.
		$pattern_id = $this->create_pattern( 'draft' );
		$this->create_flag( $pattern_id, self::$reporter );
		$this->create_flag( $pattern_id, self::$other_reporter );

		wp_update_post( array(
			'ID'          => $pattern_id,
			'post_status' => 'pending',
		) );

		return $pattern_id;
	}

	/**
	 * Reaching the threshold moves a pattern into moderator review.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\check_flag_threshold
	 */
	public function test_threshold_unpublishes_the_pattern_for_review(): void {
		update_option( 'wporg-pattern-flag_threshold', 2 );
		$pattern_id = $this->create_pattern();

		$this->create_flag( $pattern_id, self::$reporter );
		$this->assertSame( 'publish', get_post_status( $pattern_id ), 'One report is below the threshold.' );

		$this->create_flag( $pattern_id, self::$other_reporter );
		$this->assertSame( SPAM_STATUS, get_post_status( $pattern_id ) );
	}

	/**
	 * Authors cannot republish a reported pattern.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_author_cannot_republish_a_reported_pattern(): void {
		update_option( 'wporg-pattern-flag_threshold', 1 );
		$pattern_id = $this->create_pattern();
		$this->create_flag( $pattern_id, self::$reporter );

		wp_set_current_user( self::$author );
		$response = $this->publish_pattern( $pattern_id );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_pattern_cannot_change_status', $response->get_data()['code'] );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( SPAM_STATUS, get_post_status( $pattern_id ) );
	}

	/**
	 * Duplicate reports from one account count once.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\count_pending_flag_reporters
	 */
	public function test_repeat_reports_from_one_account_do_not_reach_the_threshold(): void {
		update_option( 'wporg-pattern-flag_threshold', 2 );
		$pattern_id = $this->create_pattern();

		$this->create_flag( $pattern_id, self::$reporter );
		$this->create_flag( $pattern_id, self::$reporter );
		$this->assertSame( 'publish', get_post_status( $pattern_id ), 'One account must only count once.' );

		$this->create_flag( $pattern_id, self::$other_reporter );
		$this->assertSame( SPAM_STATUS, get_post_status( $pattern_id ) );
	}

	/**
	 * Resolved reports do not count towards the threshold.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\count_pending_flag_reporters
	 */
	public function test_resolved_reports_do_not_count_towards_the_threshold(): void {
		update_option( 'wporg-pattern-flag_threshold', 2 );
		$pattern_id = $this->create_pattern();

		$flag_id = $this->create_flag( $pattern_id, self::$reporter );
		wp_update_post( array(
			'ID'          => $flag_id,
			'post_status' => 'resolved',
		) );

		$this->create_flag( $pattern_id, self::$other_reporter );
		$this->assertSame( 'publish', get_post_status( $pattern_id ) );
	}

	/**
	 * New reports do not reopen an unlisted pattern.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\check_flag_threshold
	 */
	public function test_report_does_not_reopen_an_unlisted_pattern(): void {
		update_option( 'wporg-pattern-flag_threshold', 1 );
		$pattern_id = $this->create_pattern( UNLISTED_STATUS );

		$this->create_flag( $pattern_id, self::$reporter );

		$this->assertSame( UNLISTED_STATUS, get_post_status( $pattern_id ) );
	}

	/**
	 * Removal sends one notification containing the report reasons.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Notifications\notify_pattern_flagged
	 */
	public function test_threshold_sends_a_single_notification(): void {
		update_option( 'wporg-pattern-flag_threshold', 2 );
		$pattern_id = $this->create_pattern();
		$reason     = self::factory()->term->create(
			array(
				'taxonomy'    => FLAG_REASON,
				'name'        => 'Report reason',
				'description' => 'The submitted report reason.',
			)
		);

		$flag_id = $this->create_flag( $pattern_id, self::$reporter );
		wp_set_object_terms( $flag_id, array( $reason ), FLAG_REASON );

		$messages = array();
		/**
		 * Capture notifications without delivering email.
		 *
		 * @param bool|null $result Short-circuit result.
		 * @param array     $atts   Mail arguments.
		 * @return bool
		 */
		$capture_mail = static function ( ?bool $result, array $atts ) use ( &$messages ): bool {
			$messages[] = $atts['message'];
			return true;
		};
		add_filter( 'pre_wp_mail', $capture_mail, 10, 2 );

		try {
			$this->create_flag( $pattern_id, self::$other_reporter );
		} finally {
			remove_filter( 'pre_wp_mail', $capture_mail, 10 );
		}

		$this->assertSame( SPAM_STATUS, get_post_status( $pattern_id ) );
		$this->assertCount( 1, $messages );
		$this->assertStringContainsString( 'The submitted report reason.', $messages[0] );
	}

	/**
	 * Authors cannot republish legacy removals left in pending status.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_author_cannot_publish_a_legacy_removal(): void {
		$pattern_id = $this->create_legacy_removal();

		wp_set_current_user( self::$author );
		$response = $this->publish_pattern( $pattern_id );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_pattern_under_review', $response->get_data()['code'] );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'pending', get_post_status( $pattern_id ) );
	}

	/**
	 * Moving a legacy removal to draft does not allow the author to republish it.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_author_cannot_publish_a_legacy_removal_through_a_draft(): void {
		$pattern_id = $this->create_legacy_removal();
		wp_update_post( array(
			'ID'          => $pattern_id,
			'post_status' => 'draft',
		) );

		wp_set_current_user( self::$author );
		$response = $this->publish_pattern( $pattern_id );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_pattern_under_review', $response->get_data()['code'] );
		$this->assertSame( 'draft', get_post_status( $pattern_id ) );
	}

	/**
	 * Moderators can republish legacy removals.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_moderator_can_publish_a_legacy_removal(): void {
		$pattern_id = $this->create_legacy_removal();

		wp_set_current_user( self::$moderator );
		$response = $this->publish_pattern( $pattern_id );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'publish', get_post_status( $pattern_id ) );
	}

	/**
	 * Reports below the threshold do not block author publication.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_reports_below_the_threshold_do_not_block_publishing(): void {
		update_option( 'wporg-pattern-flag_threshold', 2 );
		$pattern_id = $this->create_pattern( 'draft' );
		$this->create_flag( $pattern_id, self::$reporter );

		wp_set_current_user( self::$author );
		$response = $this->publish_pattern( $pattern_id );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'publish', get_post_status( $pattern_id ) );
	}

	/**
	 * Saving a published pattern with its current status does not trigger the report check.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_author_can_still_save_a_published_reported_pattern(): void {
		$pattern_id = $this->create_legacy_removal();
		wp_update_post( array(
			'ID'          => $pattern_id,
			'post_status' => 'publish',
		) );

		wp_set_current_user( self::$author );
		$response = $this->publish_pattern( $pattern_id );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'publish', get_post_status( $pattern_id ) );
	}

	/**
	 * After moderator approval, authors can draft and republish their pattern.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\resolve_flags_on_approval
	 */
	public function test_moderator_approval_hands_the_pattern_back_to_its_author(): void {
		update_option( 'wporg-pattern-flag_threshold', 2 );
		$pattern_id = $this->create_pattern();
		$this->create_flag( $pattern_id, self::$reporter );
		$this->create_flag( $pattern_id, self::$other_reporter );
		$this->assertSame( SPAM_STATUS, get_post_status( $pattern_id ) );

		wp_set_current_user( self::$moderator );
		$this->assertFalse( $this->publish_pattern( $pattern_id )->is_error() );

		wp_set_current_user( self::$author );
		$this->assertFalse( $this->set_pattern_status( $pattern_id, 'draft' )->is_error() );

		$response = $this->publish_pattern( $pattern_id );

		$this->assertFalse( $response->is_error(), 'An approved pattern is the author\'s to publish again.' );
		$this->assertSame( 'publish', get_post_status( $pattern_id ) );
	}

	/**
	 * Moderator approval resolves existing reports and resets the removal count.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\resolve_flags_on_approval
	 */
	public function test_approval_resolves_the_reports_it_answers(): void {
		update_option( 'wporg-pattern-flag_threshold', 2 );
		$pattern_id = $this->create_pattern();
		$this->create_flag( $pattern_id, self::$reporter );
		$this->create_flag( $pattern_id, self::$other_reporter );

		wp_set_current_user( self::$moderator );
		$this->publish_pattern( $pattern_id );

		$pending = get_posts( array(
			'post_type'   => FLAG_POST_TYPE,
			'post_parent' => $pattern_id,
			'post_status' => 'pending',
			'numberposts' => -1,
			'fields'      => 'ids',
		) );
		$this->assertSame( array(), $pending );

		wp_set_current_user( 0 );
		$this->create_flag( $pattern_id, self::$reporter );
		$this->assertSame( 'publish', get_post_status( $pattern_id ), 'Answered reports must not count again.' );
	}

	/**
	 * Author publication does not resolve reports.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\resolve_flags_on_approval
	 */
	public function test_author_publishing_does_not_resolve_a_report(): void {
		update_option( 'wporg-pattern-flag_threshold', 2 );
		$pattern_id = $this->create_pattern( 'draft' );
		$this->create_flag( $pattern_id, self::$reporter );

		wp_set_current_user( self::$author );
		$this->assertFalse( $this->publish_pattern( $pattern_id )->is_error() );

		$pending = get_posts( array(
			'post_type'   => FLAG_POST_TYPE,
			'post_parent' => $pattern_id,
			'post_status' => 'pending',
			'numberposts' => -1,
			'fields'      => 'ids',
		) );
		$this->assertCount( 1, $pending );
	}

	/**
	 * Moderator approval leaves reports below the threshold pending.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\resolve_flags_on_approval
	 */
	public function test_approval_leaves_reports_below_the_threshold_pending(): void {
		update_option( 'wporg-pattern-flag_threshold', 3 );
		$pattern_id = $this->create_pattern( 'draft' );
		$this->create_flag( $pattern_id, self::$reporter );
		$this->create_flag( $pattern_id, self::$other_reporter );

		wp_set_current_user( self::$moderator );
		$this->assertFalse( $this->publish_pattern( $pattern_id )->is_error() );

		$pending = get_posts( array(
			'post_type'   => FLAG_POST_TYPE,
			'post_parent' => $pattern_id,
			'post_status' => 'pending',
			'numberposts' => -1,
			'fields'      => 'ids',
		) );
		$this->assertCount( 2, $pending, 'Unanswered reports must stay in the moderators\' queue.' );
	}
}
