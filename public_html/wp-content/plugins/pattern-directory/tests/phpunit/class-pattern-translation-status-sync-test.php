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
use function WordPressdotorg\Pattern_Translations\sync_all_translation_statuses;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\UNLISTED_STATUS;

/**
 * The translation job only loads published originals, so it can't carry a status change away from
 * `publish` to the translations it created earlier.
 *
 * @group pattern-translation-status-sync
 */
class Pattern_Translation_Status_Sync_Test extends WP_UnitTestCase {
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
	 * Trashing the original trashes its translations, and restoring it brings them back.
	 */
	public function test_trashing_and_restoring_the_original_carries_its_translations(): void {
		$translations = $this->translate_all();

		wp_trash_post( $this->parent_id );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( 'trash', get_post_status( $translation_id ) );
		}

		wp_untrash_post( $this->parent_id );
		$restored = get_post_status( $this->parent_id );

		foreach ( $translations as $translation_id ) {
			$this->assertSame( $restored, get_post_status( $translation_id ) );
		}
	}

	/**
	 * Following the original only changes a translation's status. Core's trash path isn't used for translations:
	 * `wp_trash_post()` deletes outright when `EMPTY_TRASH_DAYS` is 0, and its meta lets the scheduled purge delete.
	 */
	public function test_trashing_the_original_never_deletes_or_schedules_deleting_translations(): void {
		$translations = $this->translate_all();
		$touched      = array();
		$record       = function ( $post_id ) use ( &$touched ) {
			$touched[] = (int) ( $post_id instanceof \WP_Post ? $post_id->ID : $post_id );
		};
		add_action( 'wp_trash_post', $record );
		add_action( 'before_delete_post', $record );

		wp_trash_post( $this->parent_id );

		$this->assertSame( array( $this->parent_id ), $touched );
		foreach ( $translations as $translation_id ) {
			$this->assertSame( 'trash', get_post_status( $translation_id ) );
			$this->assertSame( '', get_post_meta( $translation_id, '_wp_trash_meta_time', true ) );
		}
	}

	/**
	 * Scheduling a published original schedules its translations for the same time. The translations still have
	 * the original's old, past date, which `wp_update_post()` would otherwise turn back into `publish`.
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
	 * Moving a scheduled original to another time moves its translations, though the status stays `future`.
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
	 * Publishing a scheduled original early publishes its translations, which still have the scheduled date that
	 * `wp_update_post()` would otherwise keep them `future` for.
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
	 * A submitter can point `post_parent` at someone else's pattern; that doesn't make their pattern a translation.
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
	 * Following the original changes only the status: the stored bytes, dates and slug stay as the job wrote them,
	 * also when the moderator can't post unfiltered HTML.
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
	 * Translations left behind before statuses were synced are found, and only changed when applying.
	 * One whose original is gone has no status to copy, so it is counted and left as it is.
	 */
	public function test_existing_mismatches_are_reported_then_fixed(): void {
		list( $kept, $stale ) = $this->translate_all();

		$orphan = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_status' => 'publish',
				'post_parent' => PHP_INT_MAX,
				'meta_input'  => array(
					'wpop_locale'         => 'fr_FR',
					'wpop_is_translation' => true,
				),
			)
		);

		// The state production is in: originals that left `publish` before this sync existed.
		$this->set_status_without_sync( $this->parent_id, UNLISTED_STATUS );
		$this->set_status_without_sync( $kept, UNLISTED_STATUS );

		$missing  = array( 'original missing, left publish' => 1 );
		$expected = array(
			'checked'                       => 3,
			'publish to ' . UNLISTED_STATUS => 1,
		) + $missing;

		$this->assertSame( $expected, sync_all_translation_statuses( false ) );
		$this->assertSame( 'publish', get_post_status( $stale ) );

		$this->assertSame( $expected, sync_all_translation_statuses( true ) );
		$this->assertSame( UNLISTED_STATUS, get_post_status( $stale ) );
		$this->assertSame( 'publish', get_post_status( $orphan ) );

		$this->assertSame( array( 'checked' => 3 ) + $missing, sync_all_translation_statuses( true ) );
	}

	/**
	 * The scan clears runtime caches every 500 translations and carries on.
	 */
	public function test_scan_continues_past_the_cache_clearing(): void {
		self::factory()->post->create_many(
			501,
			array(
				'post_type'   => POST_TYPE,
				'post_status' => 'publish',
				'post_parent' => $this->parent_id,
				'meta_input'  => array(
					'wpop_locale'         => 'fr_FR',
					'wpop_is_translation' => true,
				),
			)
		);
		$this->set_status_without_sync( $this->parent_id, UNLISTED_STATUS );

		$this->assertSame(
			array(
				'checked'                       => 501,
				'publish to ' . UNLISTED_STATUS => 501,
			),
			sync_all_translation_statuses( false )
		);
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

	/**
	 * Change a pattern's status without firing the status hooks.
	 *
	 * @param int    $post_id Pattern ID.
	 * @param string $status  New status.
	 */
	protected function set_status_without_sync( int $post_id, string $status ): void {
		global $wpdb;

		$wpdb->update( $wpdb->posts, array( 'post_status' => $status ), array( 'ID' => $post_id ) );
		clean_post_cache( $post_id );
	}
}
