<?php
/**
 * Test that the translation job only adopts patterns it created itself.
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use WordPressdotorg\Pattern_Translations\Pattern as Translations_Pattern;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * The twice-daily translation job decides which post is the existing translation of a (pattern, locale) pair.
 * `post_parent` and `wpop_locale` are both writable by any submitter on their own pattern, so that pair alone
 * is an attacker-controlled claim; the job would then overwrite the adopted post with the parent's author and
 * status, forging authorship and bypassing review. Only `wpop_is_translation`, which the job writes and REST
 * does not expose, actually identifies a pattern the pipeline created.
 *
 * @group pattern-translation-lookup
 */
class Pattern_Translation_Lookup_Test extends WP_UnitTestCase {
	/**
	 * The locale the lookups are performed for.
	 */
	const LOCALE = 'fr_FR';

	/**
	 * The author of the English original.
	 *
	 * @var int
	 */
	protected static $victim;

	/**
	 * A directory member with no moderator capability.
	 *
	 * @var int
	 */
	protected static $member;

	/**
	 * The published English pattern the translation job walks.
	 *
	 * @var int
	 */
	protected static $parent_id;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$victim = $factory->user->create( array( 'role' => 'contributor' ) );
		self::$member = $factory->user->create( array( 'role' => 'subscriber' ) );

		self::$parent_id = $factory->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => self::$victim,
				'post_title'  => 'An English original',
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Make the test locales resolvable.
	 *
	 * `wpop_locale` is sanitized against `get_locales()`, which reads the `wporg_locales` table. That table
	 * doesn't exist in the test environment, so without this every locale sanitizes to `en_US` and the
	 * lookups below would find nothing no matter what the code under test did. `get_locales()` caches the
	 * list, and `WP_UnitTestCase` flushes the object cache per test, so prime it for each one.
	 */
	public function set_up(): void {
		parent::set_up();

		wp_cache_add_global_groups( array( 'locale-associations' ) );
		wp_cache_set( 'locale-list', array( self::LOCALE, 'de_DE' ), 'locale-associations' );
	}

	/**
	 * Clean up shared fixtures.
	 */
	public static function tear_down_after_class(): void {
		wp_delete_post( self::$parent_id, true );
		wp_delete_user( self::$victim );
		wp_delete_user( self::$member );

		parent::tear_down_after_class();
	}

	/**
	 * Create a child of the English original.
	 *
	 * @param int    $author         The child's author.
	 * @param string $locale         The child's `wpop_locale`.
	 * @param bool   $is_translation Whether to mark it as pipeline-created.
	 * @param string $status         The child's post status.
	 * @return int The new post ID.
	 */
	protected function create_child( int $author, string $locale, bool $is_translation, string $status = 'pending' ): int {
		$meta = array( 'wpop_locale' => $locale );
		if ( $is_translation ) {
			$meta['wpop_is_translation'] = true;
		}

		$post_id = self::factory()->post->create(
			array(
				'post_type'   => POST_TYPE,
				'post_author' => $author,
				'post_parent' => self::$parent_id,
				'post_status' => $status,
				'meta_input'  => $meta,
			)
		);

		/*
		 * Guard the fixture itself: `wpop_locale` silently sanitizes to `en_US` for any locale the site
		 * doesn't know, which would make every lookup below return null and pass the negative tests for
		 * entirely the wrong reason.
		 */
		$this->assertSame(
			$locale,
			get_post_meta( $post_id, 'wpop_locale', true ),
			'The fixture locale was rejected by the meta sanitizer.'
		);

		return $post_id;
	}

	/**
	 * A member's own submission claiming the same parent and locale is not adopted.
	 *
	 * @covers \WordPressdotorg\Pattern_Translations\Pattern::find_existing_translation
	 */
	public function test_ignores_a_members_post_claiming_the_parent_and_locale(): void {
		$this->create_child( self::$member, self::LOCALE, false );

		$found = Translations_Pattern::find_existing_translation( self::$parent_id, self::LOCALE );

		$this->assertNull( $found, 'A post without `wpop_is_translation` must not be adopted.' );
	}

	/**
	 * A pattern the pipeline created is still found, so genuine translations keep updating in place.
	 *
	 * @covers \WordPressdotorg\Pattern_Translations\Pattern::find_existing_translation
	 */
	public function test_finds_a_pipeline_created_translation(): void {
		$translation_id = $this->create_child( self::$victim, self::LOCALE, true );

		$found = Translations_Pattern::find_existing_translation( self::$parent_id, self::LOCALE );

		$this->assertNotNull( $found );
		$this->assertSame( $translation_id, $found->ID );
	}

	/**
	 * With both present, the pipeline's own translation wins regardless of which was created first.
	 *
	 * @covers \WordPressdotorg\Pattern_Translations\Pattern::find_existing_translation
	 */
	public function test_prefers_the_pipeline_translation_over_an_impostor(): void {
		$this->create_child( self::$member, self::LOCALE, false );
		$translation_id = $this->create_child( self::$victim, self::LOCALE, true );

		$found = Translations_Pattern::find_existing_translation( self::$parent_id, self::LOCALE );

		$this->assertNotNull( $found );
		$this->assertSame( $translation_id, $found->ID );
	}

	/**
	 * A translation for a different locale is not adopted for this one.
	 *
	 * @covers \WordPressdotorg\Pattern_Translations\Pattern::find_existing_translation
	 */
	public function test_ignores_a_translation_for_another_locale(): void {
		$this->create_child( self::$victim, 'de_DE', true );

		$found = Translations_Pattern::find_existing_translation( self::$parent_id, self::LOCALE );

		$this->assertNull( $found );
	}

	/**
	 * With nothing to adopt the job gets null, which is what makes it insert a fresh translation.
	 *
	 * @covers \WordPressdotorg\Pattern_Translations\Pattern::find_existing_translation
	 */
	public function test_returns_null_when_no_translation_exists(): void {
		$found = Translations_Pattern::find_existing_translation( self::$parent_id, self::LOCALE );

		$this->assertNull( $found );
	}
}
