<?php
/**
 * Test Block Pattern validation.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, SPAM_STATUS };

/**
 * Test pattern validation.
 *
 * @group content-validation
 */
class Pattern_Content_Validation_Test extends WP_UnitTestCase {
	protected static $pattern_id;
	protected static $user;

	/**
	 * Setup fixtures that are shared across all tests.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$pattern_id = $factory->post->create(
			array(
				'post_title' => 'Three paragraphs',
				'post_type' => POST_TYPE,
			)
		);
		self::$user = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Verify the pattern & API are set up correctly.
	 */
	public function test_pattern_directory_api() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid block content.
	 *
	 * @dataProvider data_valid_content
	 */
	public function test_valid_content( $content ) {
		wp_set_current_user( self::$user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( array( 'content' => $content ) ) );

		$response = rest_do_request( $request );

		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Data provider to test valid block content.
	 *
	 * @return array
	 */
	public function data_valid_content() {
		$two_paragraphs = "<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Two.</p>\n<!-- /wp:paragraph -->";
		$three_paragraphs = "$two_paragraphs\n\n<!-- wp:paragraph -->\n<p>Three.</p>\n<!-- /wp:paragraph -->";

		return array(
			array( $three_paragraphs ),
			array( "<!-- wp:paragraph -->\n<p><img class=\"wp-image-63\" style=\"width: 150px;\" src=\"./image.png\" alt=\"\"></p>\n<!-- /wp:paragraph -->\n\n$two_paragraphs" ),
			array( "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->\n\n$three_paragraphs" ),
			array( "$three_paragraphs\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->" ),
			array( "<!-- wp:group -->\n<div class=\"wp-block-group\">$three_paragraphs</div>\n<!-- /wp:group -->" ),
			array( "<!-- wp:group {\"layout\":{\"type\":\"flex\",\"justifyContent\":\"space-between\"}} -->\n<div class=\"wp-block-group\"><!-- wp:group -->\n<div class=\"wp-block-group\"><!-- wp:heading -->\n<h2>Heading</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Paragraph</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:group -->\n\n<!-- wp:image {\"id\":null} -->\n<figure class=\"wp-block-image\"><img src=\"./pear.png\" alt=\"\"/></figure>\n<!-- /wp:image --></div>\n<!-- /wp:group -->" ),
			array( "<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column {\"width\":\"66.66%\"} -->\n<div class=\"wp-block-column\" style=\"flex-basis:66.66%\"><!-- wp:spacer -->\n<div style=\"height:100px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer --></div>\n<!-- /wp:column -->\n\n<!-- wp:column {\"width\":\"33.33%\"} -->\n<div class=\"wp-block-column\" style=\"flex-basis:33.33%\"><!-- wp:spacer {\"height\":\"51px\"} -->\n<div style=\"height:51px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer -->\n\n<!-- wp:paragraph -->\n<p>One</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:column --></div>\n<!-- /wp:columns -->" ),
			array( "<!-- wp:query {\"queryId\":1,\"query\":{\"perPage\":3,\"pages\":0,\"offset\":0,\"postType\":\"post\",\"order\":\"desc\",\"orderBy\":\"date\",\"author\":\"\",\"search\":\"\",\"exclude\":[],\"sticky\":\"\",\"inherit\":false}} -->\n<div class=\"wp-block-query\"><!-- wp:post-template -->\n<!-- wp:post-title /-->\n\n<!-- wp:post-date /-->\n\n<!-- wp:post-excerpt /-->\n<!-- /wp:post-template -->\n\n<!-- wp:query-pagination -->\n<!-- wp:query-pagination-previous /-->\n\n<!-- wp:query-pagination-numbers /-->\n\n<!-- wp:query-pagination-next /-->\n<!-- /wp:query-pagination -->\n\n<!-- wp:query-no-results -->\n<!-- wp:paragraph {\"placeholder\":\"Add a text or blocks that will display when the query returns no results.\"} -->\n<p></p>\n<!-- /wp:paragraph -->\n<!-- /wp:query-no-results --></div>\n<!-- /wp:query -->" ),
		);
	}

	/**
	 * Test invalid block content.
	 *
	 * @dataProvider data_invalid_content
	 */
	public function test_invalid_empty_content( $expected_error_code, $content ) {
		wp_set_current_user( self::$user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( array( 'content' => $content ) ) );

		$response = rest_do_request( $request );

		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( $expected_error_code, $data['code'] );
	}

	/**
	 * Data provider to test valid block content.
	 *
	 * @return array
	 */
	public function data_invalid_content() {
		$two_paragraphs = "<!-- wp:paragraph -->\n<p>One.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Two.</p>\n<!-- /wp:paragraph -->";
		$three_paragraphs = "$two_paragraphs\n\n<!-- wp:paragraph -->\n<p>Three.</p>\n<!-- /wp:paragraph -->";

		return array(
			array( 'rest_pattern_empty', '' ),

			// Single empty paragraph.
			array( 'rest_pattern_empty_blocks', "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->" ),
			// Multiple empty paragraphs.
			array( 'rest_pattern_empty_blocks', "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->" ),
			// Empty paragraph with custom class.
			array( 'rest_pattern_empty_blocks', "<!-- wp:paragraph {\"className\":\"foo\"} -->\n<p class=\"foo\"></p>\n<!-- /wp:paragraph -->" ),
			// Empty image block.
			array( 'rest_pattern_empty_blocks', "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img alt=\"\"/></figure>\n<!-- /wp:image -->" ),
			// Empty group.
			array( 'rest_pattern_empty_blocks', "<!-- wp:group -->\n<div class=\"wp-block-group\"></div>\n<!-- /wp:group -->" ),

			array( 'rest_pattern_invalid_blocks', '<p>This is not blocks.</p>' ),
			array( 'rest_pattern_invalid_blocks', "<!-- wp:plugin/fake -->\n<p>This is some content.</p>\n<!-- /wp:plugin/fake -->" ),
			array( 'rest_pattern_invalid_blocks', "<!-- wp:group -->\n<div class=\"wp-block-group\"><!-- wp:plugin/fake -->\n<p>Fake nested block.</p>\n<!-- /wp:plugin/fake --></div>\n<!-- /wp:group -->" ),

			// Only 2 paragraphs.
			array( 'rest_pattern_insufficient_blocks', $two_paragraphs ),
			// Single group with a heading.
			array( 'rest_pattern_insufficient_blocks', "<!-- wp:group -->\n<div class=\"wp-block-group\"><!-- wp:heading -->\n<h2>One</h2>\n<!-- /wp:heading --></div>\n<!-- /wp:group -->" ),

			// 26 * 3 paragraphs = 78 blocks.
			array( 'rest_pattern_extra_blocks', str_repeat( $three_paragraphs, 26 ) ),
			// 25 * 3 paragraphs + 1 group = 76 blocks.
			array( 'rest_pattern_extra_blocks', "<!-- wp:group -->\n<div class=\"wp-block-group\">" . str_repeat( $three_paragraphs, 25 ) . "</div>\n<!-- /wp:group -->" ),
		);
	}

	/**
	 * Test a block that's detected as spam should be pending.
	 */
	public function test_spam_should_be_pending() {
		wp_set_current_user( self::$user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( array(
			'title'   => 'Spam Check',
			'content' => "<!-- wp:heading -->\n<h2 id=\"spam-check\">Spam Check.</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Paragraph: PatternDirectorySpamTest</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Third block.</p>\n<!-- /wp:paragraph -->",
			'status'  => 'publish',
		) ) );

		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error() );
		$data = $response->get_data();

		$this->assertSame( SPAM_STATUS, $data['status'] );
	}

	/**
	 * Test that paragraph-only posts should be detected as spam.
	 */
	public function test_only_paragraphs_are_spam() {
		wp_set_current_user( self::$user );

		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern/' . self::$pattern_id );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( array(
			'title'   => 'Spam Check',
			'content' => "<!-- wp:paragraph -->\n<p>Paragraph one.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Paragraph two.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Paragraph three.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Paragraph four.</p>\n<!-- /wp:paragraph -->",
			'status'  => 'publish',
		) ) );

		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error() );
		$data = $response->get_data();

		$this->assertSame( SPAM_STATUS, $data['status'] );
	}
}
