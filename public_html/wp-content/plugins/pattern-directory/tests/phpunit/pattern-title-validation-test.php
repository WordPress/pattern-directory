<?php
/**
 * Test Block Pattern validation.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Test pattern validation.
 *
 * @group title-validation
 */
class Pattern_Title_Validation_Test extends WP_UnitTestCase {
	protected static $pattern_id;
	protected static $user;
	protected static $valid_content;

	/**
	 * Setup fixtures that are shared across all tests.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$pattern_id = $factory->post->create(
			array( 'post_type' => POST_TYPE )
		);
		self::$user = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		self::$valid_content = str_repeat( "<!-- wp:paragraph -->\n<p>This is a block.</p>\n<!-- /wp:paragraph -->\n\n", 3 );
	}

	/**
	 * Test valid pattern title.
	 *
	 * @dataProvider data_valid_title
	 */
	public function test_valid_title( $pattern ) {
		wp_set_current_user( self::$user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( $pattern ) );

		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Data provider to test valid block titles.
	 *
	 * @return array
	 */
	public function data_valid_title() {
		$defaults = array(
			'status' => 'publish',
			'content' => self::$valid_content,
		);
		return array(
			array(
				array_merge( $defaults, array( 'title' => 'Default Paragraph' ) ),
			),
			array(
				array_merge( $defaults, array( 'title' => 'Testimonial' ) ),
			),
			array(
				array(
					'title' => '',
					'status' => 'draft',
					'content' => self::$valid_content,
				),
			),
		);
	}

	/**
	 * Test valid pattern title: the existing pattern already has a title.
	 */
	public function test_valid_title_already_set() {
		wp_set_current_user( self::$user );
		wp_update_post( array(
			'ID' => self::$pattern_id,
			'post_title' => 'Stylized Quote and Citation',
		) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( array( 'content' => self::$valid_content ) ) );

		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test invalid pattern titles
	 *
	 * @dataProvider data_invalid_title
	 */
	public function test_invalid_title( $expected_error_code, $pattern ) {
		wp_set_current_user( self::$user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( $pattern ) );

		$response = rest_do_request( $request );
		$this->assertTrue( $response->is_error() );

		$data = $response->get_data();
		$this->assertSame( $expected_error_code, $data['code'] );
	}

	/**
	 * Data provider to test invalid block titles.
	 *
	 * @return array
	 */
	public function data_invalid_title() {
		$defaults = array(
			'status' => 'publish',
			'content' => self::$valid_content,
		);
		return array(
			array(
				'rest_pattern_empty_title',
				array_merge( $defaults, array( 'title' => '' ) ),
			),
		);
	}

	/**
	 * Test invalid pattern title: Published pattern, has empty title with no new title.
	 */
	public function test_invalid_empty_existing_title() {
		wp_set_current_user( self::$user );
		wp_update_post( array(
			'ID' => self::$pattern_id,
			'post_title' => '',
		) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( array( 'content' => self::$valid_content ) ) );

		$response = rest_do_request( $request );
		$this->assertTrue( $response->is_error() );

		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_title', $data['code'] );
	}
}

