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
			array( 'post_type' => POST_TYPE )
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
		return array(
			array( "<!-- wp:paragraph -->\n<p>This is a block.</p>\n<!-- /wp:paragraph -->" ),
			array( "<!-- wp:paragraph -->\n<p><img class=\"wp-image-63\" style=\"width: 150px;\" src=\"./image.png\" alt=\"\"></p>\n<!-- /wp:paragraph -->" ),
			array( "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p>Some block content</p>\n<!-- /wp:paragraph -->" ),
			array( "<!-- wp:paragraph -->\n<p>Some block content</p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->" ),
			array( "<!-- wp:group -->\n<div class=\"wp-block-group\"><div class=\"wp-block-group__inner-container\"><!-- wp:image {\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"https://s.w.org/style/images/wporg-logo.svg?3\" alt=\"\"/></figure>\n<!-- /wp:image --></div></div>\n<!-- /wp:group -->" ),
			array( "<!-- wp:group {\"backgroundColor\":\"black\",\"textColor\":\"cyan-bluish-gray\"} -->\n<div class=\"wp-block-group has-cyan-bluish-gray-color has-black-background-color has-text-color has-background\"><div class=\"wp-block-group__inner-container\"><!-- wp:image {\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"https://s.w.org/style/images/wporg-logo.svg?3\" alt=\"\"/></figure>\n<!-- /wp:image --></div></div>\n<!-- /wp:group -->" ),
			array( "<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column -->\n<div class=\"wp-block-column\"><!-- wp:spacer -->\n<div style=\"height:100px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer -->\n\n<!-- wp:paragraph {\"style\":{\"typography\":{\"fontSize\":\"21px\"},\"color\":{\"text\":\"#000000\"}}} -->\n<p class=\"has-text-color\" style=\"color:#000000;font-size:21px\"><strong>We have worked with:</strong></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph {\"style\":{\"typography\":{\"fontSize\":\"24px\",\"lineHeight\":\"1.2\"}}} -->\n<p style=\"font-size:24px;line-height:1.2\"><a href=\"https://wordpress.org\">EARTHFUND™<br>ARCHWEEKLY<br>FUTURE ROADS<br>BUILDING NY</a></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:spacer -->\n<div style=\"height:100px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer --></div>\n<!-- /wp:column -->\n\n<!-- wp:column -->\n<div class=\"wp-block-column\"></div>\n<!-- /wp:column --></div>\n<!-- /wp:columns -->" ),
			array( "<!-- wp:audio {\"id\":9} -->\n<figure class=\"wp-block-audio\"><audio controls src=\"./song.mp3\"></audio></figure>\n<!-- /wp:audio -->" ),
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
		return array(
			array( 'rest_pattern_empty', '' ),
			array( 'rest_pattern_empty_blocks', "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->" ),
			array( 'rest_pattern_empty_blocks', "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->" ),
			array( 'rest_pattern_empty_blocks', "<!-- wp:paragraph {\"className\":\"foo\"} -->\n<p class=\"foo\"></p>\n<!-- /wp:paragraph -->" ),
			array( 'rest_pattern_empty_blocks', "<!-- wp:list -->\n<ul><li></li></ul>\n<!-- /wp:list -->" ),
			array( 'rest_pattern_empty_blocks', "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img alt=\"\"/></figure>\n<!-- /wp:image -->" ),
			array( 'rest_pattern_empty_blocks', "<!-- wp:group -->\n<div class=\"wp-block-group\"><div class=\"wp-block-group__inner-container\"></div></div>\n<!-- /wp:group -->" ),
			array( 'rest_pattern_empty_blocks', "<!-- wp:media-text -->\n<div class=\"wp-block-media-text alignwide is-stacked-on-mobile\"><figure class=\"wp-block-media-text__media\"></figure><div class=\"wp-block-media-text__content\"><!-- wp:paragraph {\"placeholder\":\"Content…\",\"fontSize\":\"large\"} -->\n<p class=\"has-large-font-size\"></p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:media-text -->" ),
			array( 'rest_pattern_invalid_blocks', '<p>This is not blocks.</p>' ),
			array( 'rest_pattern_invalid_blocks', "<!-- wp:plugin/fake -->\n<p>This is some content.</p>\n<!-- /wp:plugin/fake -->" ),
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
			'content' => "<!-- wp:heading -->\n<h2 id=\"spam-check\">Spam Check.</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Paragraph: PatternDirectorySpamTest</p>\n<!-- /wp:paragraph -->",
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
			'content' => "<!-- wp:paragraph -->\n<p>Paragraph one.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Paragraph two.</p>\n<!-- /wp:paragraph -->",
			'status'  => 'publish',
		) ) );

		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error() );
		$data = $response->get_data();

		$this->assertSame( SPAM_STATUS, $data['status'] );
	}
}
