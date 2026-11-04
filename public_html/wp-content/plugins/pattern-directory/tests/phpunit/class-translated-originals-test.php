<?php
/**
 * Test that locale browsing lists a translated pattern once.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_REST_Request;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\register_post_type_data;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * An English original with a translation in the requested locale is replaced by it when browsing (#314).
 *
 * @group rest-api
 */
class Translated_Originals_Test extends WP_UnitTestCase {
	/**
	 * Pattern IDs by role.
	 *
	 * @var int[]
	 */
	protected static $ids = array();

	/**
	 * Create an original translated into Spanish, one translated into French only, one with a user-submitted
	 * child claiming to be its Spanish translation, and their children.
	 *
	 * @param WP_UnitTest_Factory $factory Test data factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$ids['original']       = self::create_pattern( 'Hero cards', 'en_US' );
		self::$ids['translation']    = self::create_pattern( 'Tarjetas hero', 'es_ES', self::$ids['original'], true );
		self::$ids['french_only']    = self::create_pattern( 'About columns', 'en_US' );
		self::$ids['french']         = self::create_pattern( 'Colonnes à propos', 'fr_FR', self::$ids['french_only'], true );
		self::$ids['not_translated'] = self::create_pattern( 'About header', 'en_US' );
		self::$ids['forged_target']  = self::create_pattern( 'About footer', 'en_US' );
		self::$ids['forged']         = self::create_pattern( 'Pie de página', 'es_ES', self::$ids['forged_target'] );
	}

	/**
	 * Create a published pattern.
	 *
	 * @param string $title          The pattern title.
	 * @param string $locale         The pattern's `wpop_locale`.
	 * @param int    $parent_id      The original it claims to translate, if any.
	 * @param bool   $is_translation Whether to mark it as created by the translation pipeline.
	 * @return int The new post ID.
	 */
	private static function create_pattern( string $title, string $locale, int $parent_id = 0, bool $is_translation = false ): int {
		$meta = array( 'wpop_locale' => $locale );
		if ( $is_translation ) {
			$meta['wpop_is_translation'] = true;
		}

		return self::factory()->post->create(
			array(
				'post_title'  => $title,
				'post_type'   => POST_TYPE,
				'post_parent' => $parent_id,
				'meta_input'  => $meta,
			)
		);
	}

	/**
	 * Register the pattern meta and start from a fresh REST server, as in Endpoint_Wporg_Pattern_Test.
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
		$request = new WP_REST_Request( 'GET', '/wp/v2/' . POST_TYPE );
		$request->set_query_params( $params + array( 'per_page' => 100 ) );

		$ids = wp_list_pluck( rest_do_request( $request )->get_data(), 'id' );
		sort( $ids );

		return $ids;
	}

	/**
	 * Catches the Spanish browse listing both versions of a translated pattern, hiding originals translated
	 * into another locale only, or letting a user's own pattern that claims `post_parent` and `wpop_locale`
	 * hide someone else's original.
	 */
	public function test_browsing_lists_the_translation_in_place_of_its_original(): void {
		$expected = array(
			self::$ids['translation'],
			self::$ids['french_only'],
			self::$ids['not_translated'],
			self::$ids['forged_target'],
			self::$ids['forged'],
		);
		sort( $expected );

		$this->assertSame( $expected, $this->get_ids( array( 'locale' => 'es_ES' ) ) );
	}

	/**
	 * Catches search dropping the original when its translation matches too, as an English query must find it.
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
	 * Catches an original vanishing when the query filters out its translation, as core's request for a
	 * theme.json pattern by slug does.
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
