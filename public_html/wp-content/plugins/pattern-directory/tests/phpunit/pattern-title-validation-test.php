<?php
/**
 * Test Block Pattern validation.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Test pattern validation.
 */
class Pattern_Title_Validation_Test extends WP_UnitTestCase {
	protected static $pattern_id;
	protected static $user;

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
	}

	/**
	 * Helper function to handle REST requests to save the pattern.
	 */
	protected function save_block( $args = array() ) {
		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request_args = wp_parse_args( $args, array(
			'status' => 'publish',
			'content' => "<!-- wp:paragraph -->\n<p>This is a block.</p>\n<!-- /wp:paragraph -->",
		) );
		$request->set_body( json_encode( $request_args ) );
		return rest_do_request( $request );
	}

	/**
	 * Test valid pattern title: Add a new title.
	 */
	public function test_valid_create_title() {
		wp_set_current_user( self::$user );
		$response = $this->save_block( array( 'title' => 'Default Paragraph' ) );
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid pattern title: empty title for a draft pattern.
	 */
	public function test_valid_empty_title_draft() {
		wp_set_current_user( self::$user );
		$response = $this->save_block( array(
			'title' => '',
			'status' => 'draft',
		) );
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid pattern title: the existing pattern already has a title.
	 */
	public function test_valid_title_already_set() {
		wp_set_current_user( self::$user );
		wp_update_post( array(
			'ID' => self::$pattern_id,
			'post_title' => 'Test Title',
		) );
		$response = $this->save_block();
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test invalid pattern title: Published pattern, setting empty title.
	 */
	public function test_invalid_empty_new_title() {
		wp_set_current_user( self::$user );
		$response = $this->save_block( array(
			'status' => 'publish',
			'title' => '',
		) );
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_title', $data['code'] );
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
		$response = $this->save_block();
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_title', $data['code'] );
	}
}

