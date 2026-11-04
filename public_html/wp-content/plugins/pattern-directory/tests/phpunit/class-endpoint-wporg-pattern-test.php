<?php
/**
 * Test the pattern REST response that the Patterns API serves to core.
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
 * The pattern response matches its own schema, and its list fields stay JSON arrays.
 *
 * @group rest-api
 */
class Endpoint_Wporg_Pattern_Test extends WP_UnitTestCase {
	/**
	 * Pattern content shared by the fixture and the assertions.
	 *
	 * @var string
	 */
	const CONTENT = '<!-- wp:heading --><h2 class="wp-block-heading">Guiding your business through the project</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Experience the fusion of imagination and expertise.</p><!-- /wp:paragraph -->';

	/**
	 * A published pattern in four categories, one of them `featured`.
	 *
	 * @var int
	 */
	protected static $pattern_id;

	/**
	 * Create the pattern and its categories.
	 *
	 * @param WP_UnitTest_Factory $factory Test data factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		$term_ids = array();
		foreach ( array( 'call-to-action', 'banner', 'featured', 'services' ) as $term_name ) {
			$term_ids[] = $factory->term->create(
				array(
					'taxonomy' => 'wporg-pattern-category',
					'name'     => $term_name,
				)
			);
		}

		self::$pattern_id = $factory->post->create(
			array(
				'post_title'   => 'Services call to action with image on left',
				'post_type'    => POST_TYPE,
				'post_content' => self::CONTENT,
				'meta_input'   => array(
					'wpop_viewport_width' => 1400,
					'wpop_description'    => 'An image, title, paragraph and a CTA button to describe services.',
				),
			)
		);
		wp_set_object_terms( self::$pattern_id, $term_ids, 'wporg-pattern-category' );
	}

	/**
	 * Re-register the pattern meta and start from a fresh REST server.
	 *
	 * Post types are reset between classes, which drops their meta, and a server left by an earlier test outlives
	 * the `rest_api_init` hooks the suite rolls back, including the one that answers `OPTIONS` requests.
	 */
	public function set_up(): void {
		parent::set_up();

		register_post_type_data();
		get_post_type_object( POST_TYPE )->rest_controller = null;
		$GLOBALS['wp_rest_server']                         = null;
	}

	/**
	 * Core's Patterns tab reads `category_slugs` as a JSON array, so dropping `featured` must not leave gaps (#711).
	 *
	 * The schema check catches fields whose type drifts from what the schema advertises, but it normalizes arrays
	 * before validating them, so the slugs are compared exactly to also catch non-sequential keys.
	 */
	public function test_response_matches_schema_and_keeps_lists_sequential(): void {
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/' . POST_TYPE . '/' . self::$pattern_id ) );
		$this->assertFalse( $response->is_error() );
		$pattern = $response->get_data();

		$schema = rest_do_request( new WP_REST_Request( 'OPTIONS', '/wp/v2/' . POST_TYPE . '/' . self::$pattern_id ) )->get_data()['schema'];
		$this->assertTrue( rest_validate_value_from_schema( $pattern, $schema ) );

		$this->assertSame( self::CONTENT, $pattern['pattern_content'] );
		$this->assertSame( array( 'banner', 'call-to-action', 'services' ), $pattern['category_slugs'] );
	}
}
