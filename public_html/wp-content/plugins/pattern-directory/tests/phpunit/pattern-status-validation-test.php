<?php
/**
 * Test moderation-related pattern status validation.
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use function WordPressdotorg\Pattern_Directory\Pattern_Validation\spam_reason;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };

/**
 * A member cannot move their own pattern out of a moderator-set status (`unlisted`, spam), directly or by
 * hopping through another status, and a status-less content edit is still re-checked for spam.
 *
 * @group pattern-status-validation
 */
class Pattern_Status_Validation_Test extends WP_UnitTestCase {
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
	 * The member's pattern, whose status each test drives.
	 *
	 * @var int
	 */
	protected static $pattern_id;

	/**
	 * Three non-empty, non-paragraph-only blocks: passes content validation and reads as clean.
	 *
	 * @var string
	 */
	protected static $clean_content;

	/**
	 * The same shape, carrying the deterministic spam trigger word.
	 *
	 * @var string
	 */
	protected static $spam_content;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$moderator = $factory->user->create( array( 'role' => 'editor' ) );
		self::$member    = $factory->user->create( array( 'role' => 'subscriber' ) );

		// Blocks are separated by "\n\n" because `validate_content()` strips that sequence; a single "\n"
		// would survive as an invalid freeform block.
		self::$clean_content =
			"<!-- wp:heading --><h2>A curated layout</h2><!-- /wp:heading -->\n\n" .
			"<!-- wp:paragraph --><p>Some descriptive copy for the pattern.</p><!-- /wp:paragraph -->\n\n" .
			'<!-- wp:separator --><hr class="wp-block-separator"/><!-- /wp:separator -->';

		self::$spam_content =
			"<!-- wp:heading --><h2>A curated layout</h2><!-- /wp:heading -->\n\n" .
			"<!-- wp:paragraph --><p>PatternDirectorySpamTest buy cheap pills.</p><!-- /wp:paragraph -->\n\n" .
			'<!-- wp:separator --><hr class="wp-block-separator"/><!-- /wp:separator -->';

		self::$pattern_id = $factory->post->create(
			array(
				'post_type'    => POST_TYPE,
				'post_author'  => self::$member,
				'post_title'   => 'Stylized Quote and Citation',
				'post_content' => self::$clean_content,
				'post_status'  => 'publish',
			)
		);
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
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
	 * Put the shared pattern into a known state before a test drives it.
	 *
	 * @param string $status  Status to set.
	 * @param string $content Optional content to set.
	 */
	protected function seed_pattern( string $status, string $content = '' ): void {
		wp_update_post(
			array(
				'ID'           => self::$pattern_id,
				'post_status'  => $status,
				'post_content' => $content ?: self::$clean_content,
			)
		);
	}

	/**
	 * Count the times a pattern actually transitions into the spam status while $action runs.
	 *
	 * This is the same condition `note_spam_status()` fires on, so it stands in for "a note was written".
	 *
	 * @param callable $action The work to measure.
	 * @return int The number of transitions.
	 */
	protected function count_spam_transitions( callable $action ): int {
		$count = 0;
		$spy   = function ( $new_status, $old_status, $post ) use ( &$count ) {
			if ( POST_TYPE === $post->post_type && SPAM_STATUS === $new_status && $new_status !== $old_status ) {
				$count++;
			}
		};

		add_action( 'transition_post_status', $spy, 10, 3 );
		$action();
		remove_action( 'transition_post_status', $spy, 10 );

		return $count;
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
	 * A member cannot relist a pattern a moderator unlisted.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_member_cannot_relist_unlisted_pattern(): void {
		$this->seed_pattern( UNLISTED_STATUS );
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'status' => 'publish' ) );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_pattern_cannot_change_status', $response->get_data()['code'] );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( UNLISTED_STATUS, get_post_status( self::$pattern_id ) );
	}

	/**
	 * A member cannot walk a spam pattern out of quarantine via `unlisted` (the two-hop bypass).
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_member_cannot_move_spam_pattern_to_unlisted(): void {
		$this->seed_pattern( SPAM_STATUS );
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'status' => UNLISTED_STATUS ) );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_pattern_cannot_change_status', $response->get_data()['code'] );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( SPAM_STATUS, get_post_status( self::$pattern_id ) );
	}

	/**
	 * A member cannot publish a spam pattern directly (the guard that already worked).
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_member_cannot_publish_spam_pattern(): void {
		$this->seed_pattern( SPAM_STATUS );
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'status' => 'publish' ) );

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 'rest_pattern_cannot_change_status', $response->get_data()['code'] );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( SPAM_STATUS, get_post_status( self::$pattern_id ) );
	}

	/**
	 * A moderator can relist an unlisted pattern.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_moderator_can_relist_unlisted_pattern(): void {
		$this->seed_pattern( UNLISTED_STATUS );
		wp_set_current_user( self::$moderator );

		$response = $this->update_pattern( array( 'status' => 'publish' ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'publish', get_post_status( self::$pattern_id ) );
	}

	/**
	 * A member can still publish their own draft, the ordinary submission flow.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_status
	 */
	public function test_member_can_publish_own_draft(): void {
		$this->seed_pattern( 'draft' );
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'status' => 'publish' ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'publish', get_post_status( self::$pattern_id ) );
	}

	/**
	 * A status-less content edit is re-checked for spam, so fresh content cannot be swapped in after publish.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_against_spam
	 */
	public function test_status_less_content_edit_is_spam_checked(): void {
		$this->seed_pattern( 'publish' );
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'content' => self::$spam_content ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( SPAM_STATUS, get_post_status( self::$pattern_id ) );
	}

	/**
	 * A status-less edit with clean content leaves a published pattern published.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_against_spam
	 */
	public function test_status_less_clean_content_edit_stays_published(): void {
		$this->seed_pattern( 'publish' );
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'content' => self::$clean_content ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'publish', get_post_status( self::$pattern_id ) );
	}

	/**
	 * The autosave route is not a way around the spam check. For a draft the author owns, the autosave
	 * controller writes the live post rather than a revision, and it accepts `status`.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_against_spam
	 */
	public function test_autosave_cannot_bypass_spam_check(): void {
		$this->seed_pattern( 'draft' );
		wp_set_current_user( self::$member );

		$request = new WP_REST_Request( 'POST', '/wp/v2/' . POST_TYPE . '/' . self::$pattern_id . '/autosaves' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'status'  => 'publish',
					'content' => self::$spam_content,
				)
			)
		);
		$response = rest_do_request( $request );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( SPAM_STATUS, get_post_status( self::$pattern_id ) );
	}

	/**
	 * A moderator's edit is not spam-checked, so a false positive can't silently unpublish an approved
	 * pattern they were in the middle of correcting.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_against_spam
	 */
	public function test_moderator_edit_does_not_restatus_a_live_pattern(): void {
		$this->seed_pattern( 'publish', self::$spam_content );
		wp_set_current_user( self::$moderator );

		$response = $this->update_pattern( array( 'title' => 'A corrected title' ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'publish', get_post_status( self::$pattern_id ) );
	}

	/**
	 * A spam verdict the autosave controller throws away must not be noted against the live pattern.
	 *
	 * A published pattern can't be updated in place, so the controller files the write as a revision -- but
	 * naming a status still sends the request through the spam check, so the verdict is reached and then
	 * discarded. `note_spam_status()` hangs off `transition_post_status` precisely so nothing is recorded
	 * unless the status was actually saved.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\note_spam_status
	 */
	public function test_discarded_spam_verdict_is_not_noted(): void {
		$this->seed_pattern( 'publish' );
		wp_set_current_user( self::$member );

		$flagged     = null;
		$spy         = function ( $prepared_post ) use ( &$flagged ) {
			$flagged = $prepared_post->post_status ?? '';
			return $prepared_post;
		};
		add_filter( 'rest_pre_insert_' . POST_TYPE, $spy, 21 );

		$transitions = $this->count_spam_transitions(
			function () {
				$request = new WP_REST_Request( 'POST', '/wp/v2/' . POST_TYPE . '/' . self::$pattern_id . '/autosaves' );
				$request->set_header( 'content-type', 'application/json' );
				$request->set_body(
					wp_json_encode(
						array(
							'status'  => 'publish',
							'content' => self::$spam_content,
						)
					)
				);
				rest_do_request( $request );
			}
		);

		remove_filter( 'rest_pre_insert_' . POST_TYPE, $spy, 21 );

		$this->assertSame( SPAM_STATUS, $flagged, 'This case must reach the spam check, or it proves nothing.' );
		$this->assertSame( 0, $transitions, 'A discarded verdict must not be noted against the pattern.' );
		$this->assertSame( 'publish', get_post_status( self::$pattern_id ) );
	}

	/**
	 * A real update that does persist the spam status still reaches the note, once.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\note_spam_status
	 */
	public function test_persisted_spam_status_is_noted(): void {
		$this->seed_pattern( 'publish' );
		wp_set_current_user( self::$member );

		$transitions = $this->count_spam_transitions(
			function () {
				$this->update_pattern( array( 'content' => self::$spam_content ) );
			}
		);

		$this->assertSame( 1, $transitions );
		$this->assertSame( SPAM_STATUS, get_post_status( self::$pattern_id ) );
	}

	/**
	 * A status-less autosave doesn't reach the spam check at all, so it costs no Akismet call for a verdict
	 * that would be thrown away. Watch the prepared post: an unchecked request never carries the spam status.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_against_spam
	 */
	public function test_status_less_autosave_is_not_spam_checked(): void {
		$this->seed_pattern( 'publish' );
		wp_set_current_user( self::$member );

		$prepared_status = null;
		$spy             = function ( $prepared_post ) use ( &$prepared_status ) {
			$prepared_status = $prepared_post->post_status ?? '';
			return $prepared_post;
		};
		add_filter( 'rest_pre_insert_' . POST_TYPE, $spy, 21 );

		$request = new WP_REST_Request( 'POST', '/wp/v2/' . POST_TYPE . '/' . self::$pattern_id . '/autosaves' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'content' => self::$spam_content ) ) );
		rest_do_request( $request );

		remove_filter( 'rest_pre_insert_' . POST_TYPE, $spy, 21 );

		$this->assertNotNull( $prepared_status, 'The autosave must reach the filter for this to prove anything.' );
		$this->assertNotSame( SPAM_STATUS, $prepared_status, 'A status-less autosave must not be spam-checked.' );
	}

	/**
	 * One pattern's spam reason is never noted against another, which a batch request or an import loop
	 * would otherwise do.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\spam_reason
	 */
	public function test_spam_reason_is_kept_per_pattern(): void {
		$first  = self::$pattern_id;
		$second = self::$pattern_id + 1;

		spam_reason( $first, 'Reason for the first pattern.' );

		$this->assertSame( '', spam_reason( $second ), "Another pattern must not pick up the first's reason." );
		$this->assertSame( 'Reason for the first pattern.', spam_reason( $first ) );
		$this->assertSame( '', spam_reason( $first ), 'Reading must consume, so it cannot be noted twice.' );
	}

	/**
	 * A pattern flagged as it is created still gets its reason recorded, even though there was no ID to
	 * record it against when the check ran.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\note_spam_status
	 */
	public function test_spam_on_create_is_noted(): void {
		wp_set_current_user( self::$member );

		$request = new WP_REST_Request( 'POST', '/wp/v2/' . POST_TYPE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'title'   => 'A new pattern',
					'content' => self::$spam_content,
					'status'  => 'publish',
				)
			)
		);
		$response = rest_do_request( $request );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( SPAM_STATUS, get_post_status( $response->get_data()['id'] ) );
		$this->assertSame( '', spam_reason( 0 ), 'The reason must have been consumed by the note.' );
	}

	/**
	 * A draft is not spam-checked, so the creator's autosaves don't each cost an Akismet round trip and a
	 * work-in-progress draft can't be moved into the moderator-only quarantine while it's being written.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_against_spam
	 */
	public function test_draft_content_edit_is_not_spam_checked(): void {
		$this->seed_pattern( 'draft' );
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'content' => self::$spam_content ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( 'draft', get_post_status( self::$pattern_id ) );
	}

	/**
	 * Publishing that same draft does run the check, so skipping drafts doesn't open a way in.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\validate_against_spam
	 */
	public function test_publishing_a_spam_draft_is_caught(): void {
		$this->seed_pattern( 'draft', self::$spam_content );
		wp_set_current_user( self::$member );

		$response = $this->update_pattern( array( 'status' => 'publish' ) );

		$this->assertFalse( $response->is_error() );
		$this->assertSame( SPAM_STATUS, get_post_status( self::$pattern_id ) );
	}
}
