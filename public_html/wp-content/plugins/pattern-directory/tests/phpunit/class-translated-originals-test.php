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
	 * Create an original translated into Spanish, one translated into French only, and their translations.
	 *
	 * @param WP_UnitTest_Factory $factory Test data factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		$create = function ( string $title, string $locale, int $parent_id = 0 ) use ( $factory ): int {
			return $factory->post->create(
				array(
					'post_title'  => $title,
					'post_type'   => POST_TYPE,
					'post_parent' => $parent_id,
					'meta_input'  => array( 'wpop_locale' => $locale ),
				)
			);
		};

		self::$ids['original']       = $create( 'About cards', 'en_US' );
		self::$ids['translation']    = $create( 'Tarjetas sobre mí', 'es_ES', self::$ids['original'] );
		self::$ids['french_only']    = $create( 'About columns', 'en_US' );
		self::$ids['french']         = $create( 'Colonnes à propos', 'fr_FR', self::$ids['french_only'] );
		self::$ids['not_translated'] = $create( 'About header', 'en_US' );
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
	 * Catches the Spanish browse listing both versions of a translated pattern, or hiding originals translated
	 * into another locale only.
	 */
	public function test_browsing_lists_the_translation_in_place_of_its_original(): void {
		$expected = array( self::$ids['translation'], self::$ids['french_only'], self::$ids['not_translated'] );
		sort( $expected );

		$this->assertSame( $expected, $this->get_ids( array( 'locale' => 'es_ES' ) ) );
	}

	/**
	 * Catches search dropping the original, which an English query must still find.
	 */
	public function test_search_keeps_the_original(): void {
		$ids = $this->get_ids(
			array(
				'locale' => 'es_ES',
				'search' => 'About',
			)
		);

		$this->assertContains( self::$ids['original'], $ids );
	}
}
