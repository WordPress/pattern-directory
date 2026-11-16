<?php
/**
 * Test the log notes written when a flag's status changes.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use WordPressdotorg\InternalNotes;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Flag_Post_Type\{ PENDING_STATUS, RESOLVED_STATUS, POST_TYPE as FLAG_POST_TYPE };

/**
 * Flag status changes are logged on the pattern once the flag is saved (#480).
 *
 * @group pattern-flags
 */
class Flag_Logging_Test extends WP_UnitTestCase {
	/**
	 * The reporting user.
	 *
	 * @var \WP_User
	 */
	protected static $reporter;

	/**
	 * The flagged pattern.
	 *
	 * @var int
	 */
	protected static $pattern_id;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$reporter   = $factory->user->create_and_get( array( 'user_login' => 'flag-reporter' ) );
		self::$pattern_id = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Start each test without recorded notes.
	 */
	public function set_up(): void {
		parent::set_up();

		$GLOBALS['pattern_directory_test_notes'] = array();
	}

	/**
	 * The log messages the stand-in recorded on the pattern.
	 *
	 * @return string[]
	 */
	protected function get_log_messages(): array {
		$notes = array_filter(
			$GLOBALS['pattern_directory_test_notes'],
			function ( array $note ): bool {
				return self::$pattern_id === $note['post_parent'] && InternalNotes\LOG_POST_TYPE === ( $note['post_type'] ?? '' );
			}
		);

		return array_values( array_column( $notes, 'post_excerpt' ) );
	}

	/**
	 * Insert a flag the way the REST API does: after-insert hooks run once the request has saved everything.
	 *
	 * @param bool $fire_after_hooks Whether to run the after-insert hooks right away.
	 * @param int  $author_id        The reporter, the shared one by default.
	 * @return int The flag ID.
	 */
	protected function insert_flag( bool $fire_after_hooks = true, int $author_id = 0 ): int {
		return wp_insert_post(
			array(
				'post_type'   => FLAG_POST_TYPE,
				'post_parent' => self::$pattern_id,
				'post_author' => $author_id ? $author_id : self::$reporter->ID,
				'post_status' => PENDING_STATUS,
			),
			false,
			$fire_after_hooks
		);
	}

	/**
	 * Catches a new flag going unlogged.
	 */
	public function test_new_flag_is_logged(): void {
		$this->insert_flag();

		$this->assertSame( array( 'New flag submitted by @flag-reporter' ), $this->get_log_messages() );
	}

	/**
	 * Catches a flag logged before its save finished, as with a REST request whose later steps fail.
	 */
	public function test_flag_is_logged_only_after_it_is_fully_saved(): void {
		$flag_id = $this->insert_flag( false );

		$this->assertSame( array(), $this->get_log_messages(), 'The flag was logged before its save finished.' );

		wp_after_insert_post( $flag_id, false, null );

		$this->assertSame( array( 'New flag submitted by @flag-reporter' ), $this->get_log_messages() );
	}

	/**
	 * Catches resolving or trashing a flag going unlogged, or an unchanged status being logged.
	 */
	public function test_status_changes_are_logged(): void {
		$flag_id = $this->insert_flag();

		wp_update_post(
			array(
				'ID'         => $flag_id,
				'post_title' => 'Same status',
			)
		);
		wp_update_post(
			array(
				'ID'          => $flag_id,
				'post_status' => RESOLVED_STATUS,
			)
		);
		wp_trash_post( $flag_id );

		$resolved = get_post_status_object( RESOLVED_STATUS )->label;
		$this->assertSame(
			array(
				'New flag submitted by @flag-reporter',
				'Flag submitted by @flag-reporter marked as ' . $resolved,
				'Flag submitted by @flag-reporter moved to trash.',
			),
			$this->get_log_messages()
		);
	}

	/**
	 * Catches moderation of a flag whose reporter's account is gone dropping out of the pattern's log.
	 */
	public function test_flag_from_a_deleted_user_is_logged(): void {
		$flag_id = $this->insert_flag( true, 999999 );

		wp_trash_post( $flag_id );

		$this->assertSame(
			array(
				'New flag submitted by a deleted user',
				'Flag submitted by a deleted user moved to trash.',
			),
			$this->get_log_messages()
		);
	}
}
