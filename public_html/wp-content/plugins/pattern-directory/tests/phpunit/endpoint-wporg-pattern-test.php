<?php
/**
 * Test the Block Pattern API endpoint.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE };

/**
 * Test pattern API.
 *
 * @group rest-api
 */
class Endpoint_Wporg_Pattern_Test extends WP_UnitTestCase {
	protected static $pattern_id;

	/**
	 * Setup fixtures that are shared across all tests.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		$term_ids = [];
		foreach ( [ 'call-to-action', 'banner', 'featured', 'services' ] as $term_name ) {
			$term_ids[] = $factory->term->create(
				[
					'taxonomy' => 'wporg-pattern-category',
					'name' => $term_name,
				]
			);
		}
		self::$pattern_id = $factory->post->create(
			array(
				'post_title' => 'Services call to action with image on left',
				'post_type' => POST_TYPE,
				'post_content' => '<!-- wp:heading --><h2 class="wp-block-heading">Guiding your business through the project</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Experience the fusion of imagination and expertise with Études—the catalyst for architectural transformations that enrich the world around us.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a>Our services</a></p><!-- /wp:paragraph -->',
				'meta_input' => [
					'wpop_contains_block_types' => 'core/heading,core/paragraph',
					'wpop_viewport_width' => 1400,
					'wpop_description' => 'An image, title, paragraph and a CTA button to describe services.',
				],
			)
		);
		$factory->term->add_post_terms( self::$pattern_id, $term_ids, 'wporg-pattern-category' );
	}

	/**
	 * Verify the pattern response matches the schema, plus strict type checking
	 * for the array values.
	 *
	 * `rest_validate_value_from_schema` will check most values, but it also
	 * "normalizes" array values to associative arrays, which does not happen
	 * in practice, so we need to manually test those values.
	 */
	public function test_pattern_directory_api() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error() );
		$pattern = $response->get_data();

		// New request to get schema, so that `rest_api_init` is called (to register the custom endpoint fields).
		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$response = rest_do_request( $request );
		$schema = $response->get_data();
		$schema = $schema['schema'];

		$result = rest_validate_value_from_schema( $pattern, $schema );
		$this->assertTrue( $result );

		// Pattern content should always exist.
		$this->assertNotEmpty( $pattern['pattern_content'], 'Pattern content is empty.' );

		// Check that these arrays are sequential, not associative arrays.
		$this->assertTrue( array_is_list( $pattern['category_slugs'] ), 'Category slugs is not a sequential array.' );
		$this->assertTrue( array_is_list( $pattern['meta']['wpop_block_types'] ), 'Block types is not a sequential array.' );
	}
}
