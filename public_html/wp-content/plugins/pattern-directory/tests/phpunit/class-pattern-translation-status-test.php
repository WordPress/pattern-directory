<?php
/**
 * Test that stored translations follow their original's status.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, UNLISTED_STATUS };

/**
 * The translation job only loads published originals, so it can't unpublish their translations itself.
 *
 * @group pattern-translation-status
 */
class Pattern_Translation_Status_Test extends WP_UnitTestCase {
	/**
	 * The English original.
	 *
	 * @var int
	 */
	protected $parent_id;

	/**
	 * The original's stored translation.
	 *
	 * @var int
	 */
	protected $translation_id;

	/**
	 * Create a published original with a published translation.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->parent_id = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => 'publish',
				'meta_input'  => array( 'wpop_locale' => 'en_US' ),
			)
		);

		$this->translation_id = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => 'publish',
				'post_parent' => $this->parent_id,
				'meta_input'  => array( 'wpop_is_translation' => true ),
			)
		);
	}

	/**
	 * Unlisting the original unlists the translation, and publishing it again republishes it.
	 */
	public function test_translation_follows_unlisting_and_republishing(): void {
		wp_update_post(
			array(
				'ID'          => $this->parent_id,
				'post_status' => UNLISTED_STATUS,
			)
		);
		$this->assertSame( UNLISTED_STATUS, get_post_status( $this->translation_id ) );

		wp_update_post(
			array(
				'ID'          => $this->parent_id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'publish', get_post_status( $this->translation_id ) );
	}

	/**
	 * Trashing the original trashes the translation, and restoring it restores the translation.
	 */
	public function test_translation_follows_trash_and_restore(): void {
		wp_trash_post( $this->parent_id );
		$this->assertSame( 'trash', get_post_status( $this->translation_id ) );
		$this->assertNotEmpty( get_post_meta( $this->translation_id, '_wp_trash_meta_time', true ) );

		wp_untrash_post( $this->parent_id );
		$this->assertSame( get_post_status( $this->parent_id ), get_post_status( $this->translation_id ) );
	}

	/**
	 * Deleting the original outright trashes the translation.
	 */
	public function test_translation_is_trashed_when_original_is_deleted(): void {
		wp_delete_post( $this->parent_id, true );

		$this->assertSame( 'trash', get_post_status( $this->translation_id ) );
	}

	/**
	 * A submitter's own pattern that names the original as its parent is left alone.
	 */
	public function test_child_that_is_not_a_translation_is_untouched(): void {
		$child_id = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => 'publish',
				'post_parent' => $this->parent_id,
			)
		);

		wp_update_post(
			array(
				'ID'          => $this->parent_id,
				'post_status' => UNLISTED_STATUS,
			)
		);

		$this->assertSame( 'publish', get_post_status( $child_id ) );
	}
}
