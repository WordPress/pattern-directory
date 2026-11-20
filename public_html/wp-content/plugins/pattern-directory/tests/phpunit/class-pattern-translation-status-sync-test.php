<?php
/**
 * Test that translated patterns follow their English original's status.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WordPressdotorg\Pattern_Translations\Pattern as Translations_Pattern;
use WordPressdotorg\Pattern_Translations\PatternParser;
use function WordPressdotorg\Pattern_Translations\create_or_update_translated_pattern;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\UNLISTED_STATUS;

/**
 * Translations follow their original's status.
 *
 * @group pattern-translation-status-sync
 */
class Pattern_Translation_Status_Sync_Test extends WP_UnitTestCase {
	/**
	 * Locales the original is translated into.
	 *
	 * @var string[]
	 */
	const LOCALES = array( 'fr_FR', 'de_DE' );

	/**
	 * The English original.
	 *
	 * @var int
	 */
	protected $parent_id;

	/**
	 * `wpop_locale` sanitizes against the `wporg_locales` table, which the test environment lacks.
	 */
	public function set_up(): void {
		parent::set_up();

		wp_cache_add_global_groups( array( 'locale-associations' ) );
		wp_cache_set( 'locale-list', self::LOCALES, 'locale-associations' );

		$this->parent_id = self::factory()->post->create(
			array(
				'post_type'    => POST_TYPE,
				'post_title'   => 'Title',
				'post_status'  => 'publish',
				'post_content' => "<!-- wp:heading {\"placeholder\":\"Subtitle\"} -->\n<h2>Subtitle</h2>\n<!-- /wp:heading -->",
				'meta_input'   => array(
					'wpop_locale'      => 'en_US',
					'wpop_description' => 'Description',
				),
			)
		);
	}

	/**
	 * Unlisting the original unlists every translation of it.
	 */
	public function test_unlisting_the_original_unlists_its_translations(): void {
		$translations = $this->translate_all();

		$this->set_status( $this->parent_id, UNLISTED_STATUS );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( UNLISTED_STATUS, get_post_status( $translation_id ) );
		}
	}

	/**
	 * Publishing the original again publishes its translations again.
	 */
	public function test_republishing_the_original_republishes_its_translations(): void {
		$translations = $this->translate_all();

		$this->set_status( $this->parent_id, 'pending' );
		$this->set_status( $this->parent_id, 'publish' );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( 'publish', get_post_status( $translation_id ) );
		}
	}

	/**
	 * Trashing the original trashes its translations, with trash meta, and restoring it restores them.
	 */
	public function test_trashing_and_restoring_the_original_carries_its_translations(): void {
		$translations = $this->translate_all();

		wp_trash_post( $this->parent_id );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( 'trash', get_post_status( $translation_id ) );
			$this->assertNotSame( '', get_post_meta( $translation_id, '_wp_trash_meta_time', true ) );
		}

		wp_untrash_post( $this->parent_id );
		$restored = get_post_status( $this->parent_id );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( $restored, get_post_status( $translation_id ) );
			$this->assertSame( '', get_post_meta( $translation_id, '_wp_trash_meta_time', true ) );
		}
	}

	/**
	 * Deleting the original without going through the trash trashes its translations.
	 */
	public function test_deleting_the_original_trashes_its_translations(): void {
		$translations = $this->translate_all();

		wp_delete_post( $this->parent_id, true );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( 'trash', get_post_status( $translation_id ) );
		}
	}

	/**
	 * Scheduling a published original schedules its translations for the same time.
	 */
	public function test_scheduling_the_original_schedules_its_translations(): void {
		$translations = $this->translate_all();

		$this->schedule( $this->parent_id, '+1 day' );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( 'future', get_post_status( $translation_id ) );
			$this->assertSame( get_post( $this->parent_id )->post_date_gmt, get_post( $translation_id )->post_date_gmt );
		}
	}

	/**
	 * Rescheduling the original reschedules its translations.
	 */
	public function test_rescheduling_the_original_moves_its_translations(): void {
		$translations = $this->translate_all();

		$this->schedule( $this->parent_id, '+1 day' );
		$this->schedule( $this->parent_id, '+2 days' );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( 'future', get_post_status( $translation_id ) );
			$this->assertSame( get_post( $this->parent_id )->post_date_gmt, get_post( $translation_id )->post_date_gmt );
		}
	}

	/**
	 * Publishing a scheduled original early publishes its translations.
	 */
	public function test_publishing_a_scheduled_original_early_publishes_its_translations(): void {
		$translations = $this->translate_all();

		$this->schedule( $this->parent_id, '+1 day' );
		$this->schedule( $this->parent_id, 'now', 'publish' );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( 'publish', get_post_status( $translation_id ) );
			$this->assertSame( get_post( $this->parent_id )->post_date_gmt, get_post( $translation_id )->post_date_gmt );
		}
	}

	/**
	 * A pattern with the original as its parent but no translation marker is left alone.
	 */
	public function test_pattern_claiming_the_original_as_parent_is_left_alone(): void {
		$claimant = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => 'publish',
				'post_parent' => $this->parent_id,
				'meta_input'  => array( 'wpop_locale' => 'fr_FR' ),
			)
		);

		$this->set_status( $this->parent_id, UNLISTED_STATUS );

		$this->assertSame( 'publish', get_post_status( $claimant ) );
	}

	/**
	 * A status change on a translation doesn't travel to the original or to other translations.
	 */
	public function test_translation_status_change_stays_on_that_translation(): void {
		list( $first, $second ) = $this->translate_all();

		$this->set_status( $first, 'draft' );

		$this->assertSame( 'publish', get_post_status( $this->parent_id ) );
		$this->assertSame( 'publish', get_post_status( $second ) );
	}

	/**
	 * A translation a moderator took down stays down through its original's status changes.
	 *
	 * @dataProvider data_takedown_statuses
	 *
	 * @param string $status The status the moderator gave the translation.
	 */
	public function test_translation_taken_down_on_its_own_stays_down( string $status ): void {
		list( $taken_down, $kept ) = $this->translate_all();
		if ( 'trash' === $status ) {
			wp_trash_post( $taken_down );
		} else {
			$this->set_status( $taken_down, $status );
		}

		$this->set_status( $this->parent_id, UNLISTED_STATUS );
		wp_trash_post( $this->parent_id );
		wp_untrash_post( $this->parent_id );
		$this->set_status( $this->parent_id, 'publish' );

		$this->assertSame( $status, get_post_status( $taken_down ) );
		$this->assertSame( 'publish', get_post_status( $kept ) );
	}

	/**
	 * Statuses a moderator takes a translation down with.
	 *
	 * @return array[]
	 */
	public function data_takedown_statuses(): array {
		return array(
			'trashed'  => array( 'trash' ),
			'unlisted' => array( UNLISTED_STATUS ),
		);
	}

	/**
	 * Catches the job republishing a translation a moderator unlisted, or no longer updating its wording.
	 */
	public function test_job_updates_a_translation_taken_down_on_its_own_but_keeps_it_down(): void {
		list( $taken_down ) = $this->translate_all();
		$this->set_status( $taken_down, UNLISTED_STATUS );

		$pattern     = $this->translate( 'fr_FR', array( 'Title' => 'Nouveau titre' ) );
		$pattern->ID = $taken_down;

		$this->assertSame( $taken_down, create_or_update_translated_pattern( $pattern, $written ) );
		$this->assertTrue( $written );
		$this->assertSame( 'Nouveau titre', get_post( $taken_down )->post_title );
		$this->assertSame( UNLISTED_STATUS, get_post_status( $taken_down ) );
	}

	/**
	 * Catches a queued chunk writing a translation for an original unlisted since it was loaded.
	 */
	public function test_job_skips_an_original_unpublished_after_it_was_loaded(): void {
		global $wpdb;

		$pattern = $this->translate( 'fr_FR', array( 'Title' => 'Titre' ) );
		$wpdb->update( $wpdb->posts, array( 'post_status' => UNLISTED_STATUS ), array( 'ID' => $this->parent_id ) );

		$result = create_or_update_translated_pattern( $pattern );

		$this->assertWPError( $result );
		$this->assertSame( 'pattern_translation_original_unpublished', $result->get_error_code() );
	}

	/**
	 * Following the original changes only the status, also when the moderator can't post unfiltered HTML.
	 */
	public function test_status_sync_keeps_the_stored_translation(): void {
		$translation_id = create_or_update_translated_pattern(
			$this->translate( 'fr_FR', array( 'Subtitle' => 'C:\Temp <em>vite</em> -- fin' ) )
		);
		$before         = get_post( $translation_id );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		kses_init();
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );

		$this->set_status( $this->parent_id, UNLISTED_STATUS );
		$this->set_status( $this->parent_id, 'publish' );

		$after = get_post( $translation_id );
		foreach ( array( 'post_content', 'post_title', 'post_name', 'post_date', 'post_date_gmt', 'post_author' ) as $field ) {
			$this->assertSame( $before->$field, $after->$field, $field );
		}
	}

	/**
	 * Store a translation of the original for each locale, as the job does.
	 *
	 * @return int[] Translation IDs, in `LOCALES` order.
	 */
	protected function translate_all(): array {
		$ids = array();
		foreach ( self::LOCALES as $locale ) {
			$ids[] = create_or_update_translated_pattern( $this->translate( $locale, array( 'Title' => "Title {$locale}" ) ) );
		}

		return $ids;
	}

	/**
	 * Run the original through the parser with the given translator strings, as the job does.
	 *
	 * @param string $locale       Locale of the translation.
	 * @param array  $replacements Translations keyed by original string.
	 * @return Translations_Pattern The assembled translation.
	 */
	protected function translate( string $locale, array $replacements ): Translations_Pattern {
		$parent = Translations_Pattern::from_post( get_post( $this->parent_id ) );

		$pattern         = ( new PatternParser( $parent ) )->replace_strings_with_kses( $replacements );
		$pattern->ID     = 0;
		$pattern->locale = $locale;
		$pattern->parent = $parent;

		return $pattern;
	}

	/**
	 * Change a pattern's status the way moderation does.
	 *
	 * @param int    $post_id Pattern ID.
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
	 * Give a pattern a date and status, as an edit that schedules or publishes it does.
	 *
	 * @param int    $post_id Pattern ID.
	 * @param string $when    Relative time, for `strtotime()`.
	 * @param string $status  Status to save with the date.
	 */
	protected function schedule( int $post_id, string $when, string $status = 'future' ): void {
		$gmt = gmdate( 'Y-m-d H:i:s', strtotime( $when ) );

		wp_update_post(
			array(
				'ID'            => $post_id,
				'post_status'   => $status,
				'post_date'     => get_date_from_gmt( $gmt ),
				'post_date_gmt' => $gmt,
				'edit_date'     => true,
			)
		);
	}
}
