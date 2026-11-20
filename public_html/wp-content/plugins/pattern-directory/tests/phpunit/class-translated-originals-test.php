<?php
/**
 * Test that locale browsing lists a translated pattern once.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * An English original with a translation in the requested locale is replaced by it when browsing (#314).
 *
 * @group rest-api
 */
class Translated_Originals_Test extends \WP_UnitTestCase {
	/**
	 * Pattern IDs by role.
	 *
	 * @var int[]
	 */
	protected static $ids = array();

	/**
	 * Create originals translated into Spanish, into French only, and not at all.
	 *
	 * @param \WP_UnitTest_Factory $factory Test data factory.
	 */
	public static function wpSetUpBeforeClass( \WP_UnitTest_Factory $factory ): void {
		self::$ids['original']       = self::create_pattern( 'Hero cards', 'en_US' );
		self::$ids['translation']    = self::create_pattern( 'Tarjetas hero', 'es_ES', self::$ids['original'] );
		self::$ids['french_only']    = self::create_pattern( 'About columns', 'en_US' );
		self::$ids['french']         = self::create_pattern( 'Colonnes à propos', 'fr_FR', self::$ids['french_only'] );
		self::$ids['not_translated'] = self::create_pattern( 'About header', 'en_US' );
	}

	/**
	 * Create a published pattern.
	 *
	 * @param string $title     The pattern title.
	 * @param string $locale    The pattern's `wpop_locale`.
	 * @param int    $parent_id The original it translates, if any.
	 * @return int The new post ID.
	 */
	private static function create_pattern( string $title, string $locale, int $parent_id = 0 ): int {
		return self::factory()->post->create(
			array(
				'post_title'  => $title,
				'post_type'   => POST_TYPE,
				'post_parent' => $parent_id,
				'meta_input'  => array( 'wpop_locale' => $locale ),
			)
		);
	}

	/**
	 * Register the pattern meta and start from a fresh REST server.
	 */
	public function set_up(): void {
		parent::set_up();

		register_post_type_data();
		get_post_type_object( POST_TYPE )->rest_controller = null;
		$GLOBALS['wp_rest_server']                         = null;
	}

	/**
	 * Fetch the IDs of the patterns the collection lists.
	 *
	 * @param array $params Query parameters.
	 * @return int[] Sorted pattern IDs.
	 */
	private function get_ids( array $params ): array {
		$request = new \WP_REST_Request( 'GET', '/wp/v2/' . POST_TYPE );
		$request->set_query_params( $params + array( 'per_page' => 100 ) );

		$ids = wp_list_pluck( rest_do_request( $request )->get_data(), 'id' );
		sort( $ids );

		return $ids;
	}

	/**
	 * Catches Spanish browsing listing both versions, or hiding an original translated into French only.
	 */
	public function test_browsing_lists_the_translation_in_place_of_its_original(): void {
		$expected = array(
			self::$ids['translation'],
			self::$ids['french_only'],
			self::$ids['not_translated'],
		);
		sort( $expected );

		$this->assertSame( $expected, $this->get_ids( array( 'locale' => 'es_ES' ) ) );
	}

	/**
	 * Catches the label missing a translation being published, unpublished or deleted.
	 */
	public function test_original_follows_its_translation_being_published_unpublished_and_deleted(): void {
		$translation = self::create_pattern( 'Cabecera', 'es_ES', self::$ids['not_translated'] );
		$this->assertNotContains( self::$ids['not_translated'], $this->get_ids( array( 'locale' => 'es_ES' ) ) );

		wp_update_post(
			array(
				'ID'          => $translation,
				'post_status' => 'draft',
			)
		);
		$this->assertContains( self::$ids['not_translated'], $this->get_ids( array( 'locale' => 'es_ES' ) ) );

		$deleted = self::create_pattern( 'Encabezado', 'es_ES', self::$ids['not_translated'] );
		$this->assertNotContains( self::$ids['not_translated'], $this->get_ids( array( 'locale' => 'es_ES' ) ) );

		wp_delete_post( $deleted, true );
		$this->assertContains( self::$ids['not_translated'], $this->get_ids( array( 'locale' => 'es_ES' ) ) );
	}

	/**
	 * Catches search dropping the original, which an English query must find.
	 */
	public function test_search_keeps_the_original(): void {
		$ids = $this->get_ids(
			array(
				'locale' => 'es_ES',
				'search' => 'hero',
			)
		);

		$this->assertContains( self::$ids['translation'], $ids );
		$this->assertContains( self::$ids['original'], $ids );
	}

	/**
	 * Catches the Popular sort dropping a pattern whose translation has no favorites.
	 */
	public function test_popular_sort_keeps_the_original(): void {
		update_post_meta( self::$ids['original'], 'wporg-pattern-favorites', 3 );

		$ids = $this->get_ids(
			array(
				'locale'  => 'es_ES',
				'orderby' => 'favorite_count',
			)
		);

		$this->assertContains( self::$ids['original'], $ids );
	}

	/**
	 * Catches a request by slug, as core makes for theme.json patterns, losing the original.
	 */
	public function test_original_stays_when_its_translation_is_filtered_out(): void {
		$ids = $this->get_ids(
			array(
				'locale' => 'es_ES',
				'slug'   => array( 'hero-cards' ),
			)
		);

		$this->assertSame( array( self::$ids['original'] ), $ids );
	}
}
