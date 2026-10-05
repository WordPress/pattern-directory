<?php
/**
 * Test the moderator's unlisting message to the pattern author.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use function WordPressdotorg\Pattern_Directory\Notifications\register_unlisted_meta;
use const WordPressdotorg\Pattern_Directory\Notifications\UNLISTED_DETAIL_META;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS };

/**
 * The message a moderator writes in the Unlist modal reaches the author's email once, is never public,
 * and only a moderator can set it.
 *
 * @group notifications
 */
class Unlist_Message_Test extends WP_UnitTestCase {
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
	 * The member's pattern.
	 *
	 * @var int
	 */
	protected static $pattern_id;

	/**
	 * Email bodies sent during the current test.
	 *
	 * @var string[]
	 */
	protected $messages = array();

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
				'post_type'    => POST_TYPE,
				'post_author'  => self::$member,
				'post_title'   => 'Stylized Quote and Citation',
				'post_content' =>
					"<!-- wp:heading --><h2>A curated layout</h2><!-- /wp:heading -->\n\n" .
					"<!-- wp:paragraph --><p>Some descriptive copy for the pattern.</p><!-- /wp:paragraph -->\n\n" .
					'<!-- wp:separator --><hr class="wp-block-separator"/><!-- /wp:separator -->',
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
	 * Register the meta, capture email without delivering it, and start each test from a published pattern.
	 */
	public function set_up(): void {
		parent::set_up();

		/*
		 * The test case resets post types between classes, which drops their registered meta. Register it
		 * again, and drop the cached controller and server so the REST schema picks it up.
		 */
		register_unlisted_meta();
		get_post_type_object( POST_TYPE )->rest_controller = null;
		$GLOBALS['wp_rest_server']                         = null;

		wp_update_post(
			array(
				'ID'          => self::$pattern_id,
				'post_status' => 'publish',
			)
		);
		delete_post_meta( self::$pattern_id, UNLISTED_DETAIL_META );

		$this->messages = array();
		add_filter( 'pre_wp_mail', array( $this, 'capture_mail' ), 10, 2 );
	}

	/**
	 * Stop capturing email and reset the current user.
	 */
	public function tear_down(): void {
		remove_filter( 'pre_wp_mail', array( $this, 'capture_mail' ), 10 );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Record an email body instead of sending it.
	 *
	 * @param bool|null $result Short-circuit result.
	 * @param array     $atts   Mail arguments.
	 * @return bool
	 */
	public function capture_mail( ?bool $result, array $atts ): bool {
		$this->messages[] = $atts['message'];
		return true;
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
	 * The message reaches the author, even one that mentions a directive, and is consumed by it.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Notifications\notify_pattern_unlisted
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Validation\get_rendered_meta
	 */
	public function test_message_is_emailed_once(): void {
		wp_set_current_user( self::$moderator );
		$detail = 'Please remove the data-wp-interactive attribute and resubmit.';

		$response = $this->update_pattern(
			array(
				'status' => UNLISTED_STATUS,
				'meta'   => array( UNLISTED_DETAIL_META => $detail ),
			)
		);

		$this->assertFalse( $response->is_error() );
		$this->assertSame( UNLISTED_STATUS, get_post_status( self::$pattern_id ) );
		$this->assertCount( 1, $this->messages );
		$this->assertStringContainsString( $detail, $this->messages[0] );
		$this->assertSame( '', get_post_meta( self::$pattern_id, UNLISTED_DETAIL_META, true ) );

		// Relisted, then unlisted again from the list screen: the earlier message is not resent.
		wp_update_post(
			array(
				'ID'          => self::$pattern_id,
				'post_status' => 'publish',
			)
		);
		wp_update_post(
			array(
				'ID'          => self::$pattern_id,
				'post_status' => UNLISTED_STATUS,
			)
		);

		$this->assertCount( 3, $this->messages );
		$this->assertStringNotContainsString( $detail, $this->messages[2] );
	}

	/**
	 * The author cannot set the message themselves.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Notifications\register_unlisted_meta
	 */
	public function test_member_cannot_set_message(): void {
		wp_set_current_user( self::$member );

		$response = $this->update_pattern(
			array( 'meta' => array( UNLISTED_DETAIL_META => 'Written by the author.' ) )
		);

		$this->assertTrue( $response->is_error() );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( '', get_post_meta( self::$pattern_id, UNLISTED_DETAIL_META, true ) );
	}

	/**
	 * The message is never in the public API.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Notifications\register_unlisted_meta
	 */
	public function test_message_is_not_public(): void {
		update_post_meta( self::$pattern_id, UNLISTED_DETAIL_META, 'For the author only.' );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/' . POST_TYPE . '/' . self::$pattern_id ) );

		$this->assertFalse( $response->is_error() );
		$this->assertArrayNotHasKey( UNLISTED_DETAIL_META, $response->get_data()['meta'] );
	}
}
