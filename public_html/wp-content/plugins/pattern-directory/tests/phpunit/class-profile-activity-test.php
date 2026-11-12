<?php
/**
 * Test the activity item that a published pattern adds to its author's WordPress.org profile.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS };

require_once __DIR__ . '/stubs/profile-helpers.php';

/**
 * A pattern's first publication adds one activity item to its author's profile. Updates, republishing and
 * translations add none.
 *
 * @group profile-activity
 */
class Profile_Activity_Test extends WP_UnitTestCase {
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
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$moderator = $factory->user->create( array( 'role' => 'editor' ) );
		self::$member    = $factory->user->create( array( 'role' => 'subscriber' ) );
	}

	/**
	 * Start each test with no recorded calls.
	 */
	public function set_up(): void {
		parent::set_up();

		if ( empty( $GLOBALS['wporg_profile_activity_stub'] ) ) {
			$this->markTestSkipped( 'The real profiles API is loaded, so its calls cannot be recorded.' );
		}

		$GLOBALS['wporg_profile_activity_calls']  = array();
		$GLOBALS['wporg_profile_activity_result'] = true;
	}

	/**
	 * Create a pattern written by the member.
	 *
	 * @param string $status Post status.
	 * @param string $title  Post title.
	 *
	 * @return int Pattern ID.
	 */
	protected function create_pattern( string $status, string $title = 'Stylized Quote and Citation' ): int {
		return self::factory()->post->create(
			array(
				'post_type'    => POST_TYPE,
				'post_author'  => self::$member,
				'post_title'   => $title,
				'post_content' => '<!-- wp:paragraph --><p>Some copy.</p><!-- /wp:paragraph -->',
				'post_status'  => $status,
			)
		);
	}

	/**
	 * Change a post's status.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $status  New status.
	 */
	protected function set_status( int $post_id, string $status ): void {
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => $status,
			)
		);
	}

	/**
	 * Recorded calls to the profiles API.
	 *
	 * @return array[]
	 */
	protected function calls(): array {
		return $GLOBALS['wporg_profile_activity_calls'];
	}

	/**
	 * Publishing a draft adds one activity item that names and links the pattern.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_publishing_a_pattern_adds_one_activity_item(): void {
		$pattern_id = $this->create_pattern( 'draft' );
		$this->set_status( $pattern_id, 'publish' );

		$permalink = get_permalink( $pattern_id );

		$this->assertSame(
			array(
				array(
					'patterns',
					'pattern_create',
					self::$member,
					array(
						'primary_link' => $permalink,
						'item_id'      => $pattern_id,
						'message'      => sprintf( 'Published a new pattern, <a href="%s">Stylized Quote and Citation</a>', esc_url( $permalink ) ),
					),
				),
			),
			$this->calls()
		);
	}

	/**
	 * When a moderator approves a pending pattern, the activity item goes to the pattern's author.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_approval_by_a_moderator_credits_the_author(): void {
		$pattern_id = $this->create_pattern( 'pending' );

		wp_set_current_user( self::$moderator );
		$this->set_status( $pattern_id, 'publish' );

		$this->assertCount( 1, $this->calls() );
		$this->assertSame( self::$member, $this->calls()[0][2] );
	}

	/**
	 * Saving a pattern that is already published adds nothing.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_updating_a_published_pattern_adds_nothing(): void {
		$pattern_id = $this->create_pattern( 'publish' );

		wp_update_post(
			array(
				'ID'         => $pattern_id,
				'post_title' => 'Stylized Quote and Citation, updated',
			)
		);

		$this->assertCount( 1, $this->calls() );
	}

	/**
	 * A pattern that was published before this change has no marker, and editing it adds nothing.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_editing_a_pattern_published_before_this_change_adds_nothing(): void {
		$pattern_id = $this->create_pattern( 'publish' );
		delete_post_meta( $pattern_id, '_wpop_profile_activity_sent' );
		$GLOBALS['wporg_profile_activity_calls'] = array();

		wp_update_post(
			array(
				'ID'         => $pattern_id,
				'post_title' => 'Stylized Quote and Citation, updated',
			)
		);

		$this->assertSame( array(), $this->calls() );
	}

	/**
	 * A pattern that is unlisted and later published again does not get a second item.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_publishing_again_after_unlisting_adds_nothing(): void {
		$pattern_id = $this->create_pattern( 'publish' );

		$this->set_status( $pattern_id, UNLISTED_STATUS );
		$this->set_status( $pattern_id, 'publish' );

		$this->assertCount( 1, $this->calls() );
	}

	/**
	 * A translation is its own post with the original as its parent and the same author, so publishing it adds
	 * nothing.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_publishing_a_translation_adds_nothing(): void {
		$original_id = $this->create_pattern( 'publish' );

		self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$member,
				'post_parent' => $original_id,
				'post_title'  => 'Cita y citación estilizadas',
				'post_status' => 'publish',
				'meta_input'  => array( 'wpop_locale' => 'es_ES' ),
			)
		);

		$this->assertCount( 1, $this->calls() );
		$this->assertSame( $original_id, $this->calls()[0][3]['item_id'] );
	}

	/**
	 * Other post types are left to their own notifiers.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_other_post_types_add_nothing(): void {
		self::factory()->post->create(
			array(
				'post_author' => self::$member,
				'post_status' => 'publish',
			)
		);

		$this->assertSame( array(), $this->calls() );
	}

	/**
	 * When the item cannot be queued, the pattern is not marked, so the next publication tries again.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_a_failed_item_is_tried_again_on_the_next_publication(): void {
		$GLOBALS['wporg_profile_activity_result'] = false;
		$pattern_id                               = $this->create_pattern( 'publish' );

		$GLOBALS['wporg_profile_activity_result'] = true;
		$this->set_status( $pattern_id, UNLISTED_STATUS );
		$this->set_status( $pattern_id, 'publish' );
		$this->set_status( $pattern_id, UNLISTED_STATUS );
		$this->set_status( $pattern_id, 'publish' );

		$this->assertCount( 2, $this->calls() );
	}

	/**
	 * The pattern title is escaped in the activity message.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Profile_Activity\maybe_add_published_activity
	 */
	public function test_the_title_is_escaped(): void {
		$pattern_id = $this->create_pattern( 'publish', 'Hero <b>Call</b>' );

		// Titles keep basic tags such as `<b>` when saved, so the message has to escape them.
		$this->assertStringContainsString( '<b>', get_the_title( $pattern_id ) );
		$this->assertStringContainsString( '>Hero &lt;b&gt;Call&lt;/b&gt;</a>', $this->calls()[0][3]['message'] );
	}
}
