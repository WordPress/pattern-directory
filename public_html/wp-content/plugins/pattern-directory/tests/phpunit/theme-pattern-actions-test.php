<?php
/**
 * Test the theme's front-end pattern actions.
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS, SPAM_STATUS };

/**
 * `do_pattern_actions()` drafts a pattern through `wp_update_post()` rather than the REST API, so
 * `validate_status()` never sees it. Without its own check an author could draft their way out of a
 * moderator-set status here and then publish the draft over REST.
 *
 * @group pattern-status-validation
 */
class Theme_Pattern_Actions_Test extends WP_UnitTestCase {
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

		/*
		 * The suite loads plugins, not the theme, so pull in the one file under test. Its block and inc
		 * requires are all `__DIR__`-relative and load standalone.
		 */
		if ( ! function_exists( '\WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions' ) ) {
			require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/themes/wporg-pattern-directory-2024/functions.php';
		}
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		wp_delete_user( self::$moderator );
		wp_delete_user( self::$member );

		parent::tear_down_after_class();
	}

	/**
	 * Stop `wp_safe_redirect()` reaching `header()`, which PHPUnit's buffered output makes fatal.
	 *
	 * @var callable
	 */
	protected $suppress_redirect;

	/**
	 * The location the theme last tried to redirect to.
	 *
	 * @var string
	 */
	protected $redirected_to = '';

	/**
	 * Suppress redirects for the duration of each test.
	 */
	public function set_up(): void {
		parent::set_up();

		// An empty location makes `wp_redirect()` bail before it sends any header.
		$this->redirected_to     = '';
		$this->suppress_redirect = function ( $location ) {
			$this->redirected_to = (string) $location;
			return '';
		};
		add_filter( 'wp_redirect', $this->suppress_redirect );
	}

	/**
	 * Reset the current user and request state between tests.
	 */
	public function tear_down(): void {
		remove_filter( 'wp_redirect', $this->suppress_redirect );
		unset( $_REQUEST['action'], $_REQUEST['_wpnonce'] );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Run the theme's draft action against a pattern, as the current user.
	 *
	 * @param int $pattern_id The pattern to act on.
	 */
	protected function do_draft_action( int $pattern_id ): void {
		$this->go_to( get_permalink( $pattern_id ) );

		$_REQUEST['action']   = 'draft';
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'draft-' . $pattern_id );

		\WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions();
	}

	/**
	 * Create a pattern owned by the member.
	 *
	 * @param string $status The pattern's status.
	 * @return int The new pattern ID.
	 */
	protected function create_pattern( string $status ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$member,
				'post_status' => $status,
			)
		);
	}

	/**
	 * An author cannot draft their way out of a moderator's removal.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_member_cannot_draft_an_unlisted_pattern(): void {
		$pattern_id = $this->create_pattern( UNLISTED_STATUS );
		wp_set_current_user( self::$member );

		$this->do_draft_action( $pattern_id );

		$this->assertSame( UNLISTED_STATUS, get_post_status( $pattern_id ) );
		$this->assertStringContainsString(
			'status=draft-not-allowed',
			$this->redirected_to,
			'The author must be told why, not left on a page that silently does nothing.'
		);
	}

	/**
	 * Nor out of the spam quarantine.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_member_cannot_draft_a_spam_pattern(): void {
		$pattern_id = $this->create_pattern( SPAM_STATUS );
		wp_set_current_user( self::$member );

		$this->do_draft_action( $pattern_id );

		$this->assertSame( SPAM_STATUS, get_post_status( $pattern_id ) );
	}

	/**
	 * The ordinary case still works: an author can unpublish their own published pattern.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_member_can_draft_own_published_pattern(): void {
		$pattern_id = $this->create_pattern( 'publish' );
		wp_set_current_user( self::$member );

		$this->do_draft_action( $pattern_id );

		$this->assertSame( 'draft', get_post_status( $pattern_id ) );
	}

	/**
	 * A moderator can still draft an unlisted pattern.
	 *
	 * @covers \WordPressdotorg\Theme\Pattern_Directory_2024\do_pattern_actions
	 */
	public function test_moderator_can_draft_an_unlisted_pattern(): void {
		$pattern_id = $this->create_pattern( UNLISTED_STATUS );
		wp_set_current_user( self::$moderator );

		$this->do_draft_action( $pattern_id );

		$this->assertSame( 'draft', get_post_status( $pattern_id ) );
	}
}
