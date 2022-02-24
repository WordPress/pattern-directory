<?php
/**
 * Test Block Pattern validation.
 */

use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\{ POST_TYPE, SPAM_STATUS };

/**
 * Test pattern validation.
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
	 * Helper function to handle REST requests to save the pattern.
	 */
	protected function save_block_content( $content ) {
		return $this->save_block_pattern( compact( 'content' ) );
	}

	/**
	 * Helper function to handle a REST request to save a full pattern.
	 */
	protected function save_block_pattern( $attributes ) {
		$request = new WP_REST_Request( 'POST', '/wp/v2/wporg-pattern' . ( self::$pattern_id ? '/' . self::$pattern_id : '' ) );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( json_encode( $attributes ) );

		return rest_do_request( $request );
	}

	/**
	 * Test valid block content: simple paragraph.
	 */
	public function test_valid_simple_block() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:paragraph -->\n<p>This is a block.</p>\n<!-- /wp:paragraph -->"
		);
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid block content: paragraph with only an image.
	 */
	public function test_valid_block_with_image() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:paragraph -->\n<p><img class=\"wp-image-63\" style=\"width: 150px;\" src=\"./image.png\" alt=\"\"></p>\n<!-- /wp:paragraph -->"
		);
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid block content: real content with an extra empty paragraph (beginning).
	 */
	public function test_valid_extra_empty_paragraph_initial() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p>Some block content</p>\n<!-- /wp:paragraph -->"
		);
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid block content: real content with an extra empty paragraph (end).
	 */
	public function test_valid_extra_empty_paragraph_end() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:paragraph -->\n<p>Some block content</p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->"
		);
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid block content: a group block with an image.
	 */
	public function test_valid_group_block() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:group -->\n<div class=\"wp-block-group\"><div class=\"wp-block-group__inner-container\"><!-- wp:image {\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"https://s.w.org/style/images/wporg-logo.svg?3\" alt=\"\"/></figure>\n<!-- /wp:image --></div></div>\n<!-- /wp:group -->"
		);
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid block content: a group block with an image and a background color.
	 */
	public function test_valid_group_block_with_color() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:group {\"backgroundColor\":\"black\",\"textColor\":\"cyan-bluish-gray\"} -->\n<div class=\"wp-block-group has-cyan-bluish-gray-color has-black-background-color has-text-color has-background\"><div class=\"wp-block-group__inner-container\"><!-- wp:image {\"sizeSlug\":\"large\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"https://s.w.org/style/images/wporg-logo.svg?3\" alt=\"\"/></figure>\n<!-- /wp:image --></div></div>\n<!-- /wp:group -->"
		);
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid block content: two columns, one empty, should still be valid.
	 */
	public function test_valid_columns_block() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:columns -->\n<div class=\"wp-block-columns\"><!-- wp:column -->\n<div class=\"wp-block-column\"><!-- wp:spacer -->\n<div style=\"height:100px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer -->\n\n<!-- wp:paragraph {\"style\":{\"typography\":{\"fontSize\":\"21px\"},\"color\":{\"text\":\"#000000\"}}} -->\n<p class=\"has-text-color\" style=\"color:#000000;font-size:21px\"><strong>We have worked with:</strong></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph {\"style\":{\"typography\":{\"fontSize\":\"24px\",\"lineHeight\":\"1.2\"}}} -->\n<p style=\"font-size:24px;line-height:1.2\"><a href=\"https://wordpress.org\">EARTHFUND™<br>ARCHWEEKLY<br>FUTURE ROADS<br>BUILDING NY</a></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:spacer -->\n<div style=\"height:100px\" aria-hidden=\"true\" class=\"wp-block-spacer\"></div>\n<!-- /wp:spacer --></div>\n<!-- /wp:column -->\n\n<!-- wp:column -->\n<div class=\"wp-block-column\"></div>\n<!-- /wp:column --></div>\n<!-- /wp:columns -->"
		);
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test valid block content: an audio block.
	 */
	public function test_valid_audio_block() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:audio {\"id\":9} -->\n<figure class=\"wp-block-audio\"><audio controls src=\"./song.mp3\"></audio></figure>\n<!-- /wp:audio -->"
		);
		$this->assertFalse( $response->is_error() );
	}

	/**
	 * Test invalid block content: empty content.
	 */
	public function test_invalid_empty_content() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content( '' );
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty', $data['code'] );
	}

	/**
	 * Test invalid block content: not blocks.
	 */
	public function test_invalid_not_blocks() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content( '<p>This is not blocks.</p>' );
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_invalid_blocks', $data['code'] );
	}

	/**
	 * Test invalid block content: empty paragraph (default block).
	 */
	public function test_invalid_empty_paragraph() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content( "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->" );
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_blocks', $data['code'] );
	}

	/**
	 * Test invalid block content: empty paragraphs (multiple).
	 */
	public function test_invalid_empty_paragraphs() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content( "<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --><!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->" );
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_blocks', $data['code'] );
	}

	/**
	 * Test invalid block content: empty paragraph, with a class.
	 */
	public function test_invalid_block_with_class() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:paragraph {\"className\":\"foo\"} -->\n<p class=\"foo\"></p>\n<!-- /wp:paragraph -->"
		);
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_blocks', $data['code'] );
	}

	/**
	 * Test invalid block content: empty list (not default).
	 */
	public function test_invalid_empty_list() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content( "<!-- wp:list -->\n<ul><li></li></ul>\n<!-- /wp:list -->" );
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_blocks', $data['code'] );
	}

	/**
	 * Test invalid block content: empty image.
	 */
	public function test_invalid_empty_image() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content( "<!-- wp:image -->\n<figure class=\"wp-block-image\"><img alt=\"\"/></figure>\n<!-- /wp:image -->" );
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_blocks', $data['code'] );
	}

	/**
	 * Test invalid block content: a group block with an image.
	 */
	public function test_invalid_empty_group_block() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:group -->\n<div class=\"wp-block-group\"><div class=\"wp-block-group__inner-container\"></div></div>\n<!-- /wp:group -->"
		);
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_blocks', $data['code'] );
	}

	/**
	 * Test invalid block content: an empty media & text block.
	 */
	public function test_invalid_empty_media_text_block() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:media-text -->\n<div class=\"wp-block-media-text alignwide is-stacked-on-mobile\"><figure class=\"wp-block-media-text__media\"></figure><div class=\"wp-block-media-text__content\"><!-- wp:paragraph {\"placeholder\":\"Content…\",\"fontSize\":\"large\"} -->\n<p class=\"has-large-font-size\"></p>\n<!-- /wp:paragraph --></div></div>\n<!-- /wp:media-text -->"
		);
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_empty_blocks', $data['code'] );
	}

	/**
	 * Test invalid block content: a block that doesn't exist on this site.
	 */
	public function test_invalid_fake_block() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_content(
			"<!-- wp:plugin/fake -->\n<p>This is some content.</p>\n<!-- /wp:plugin/fake -->"
		);
		$this->assertTrue( $response->is_error() );
		$data = $response->get_data();
		$this->assertSame( 'rest_pattern_invalid_blocks', $data['code'] );
	}

	/**
	 * Test a block that's detected as spam should be pending.
	 */
	public function test_spam_should_be_pending() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_pattern( array(
			'title'   => 'Spam Check',
			'content' => "<!-- wp:heading -->\n<h2 id=\"spam-check\">Spam Check.</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Paragraph: PatternDirectorySpamTest</p>\n<!-- /wp:paragraph -->",
			'status'  => 'publish',
		) );

		$this->assertFalse( $response->is_error() );
		$data = $response->get_data();

		$this->assertSame( SPAM_STATUS, $data['status'] );
	}

	/**
	 * Test that paragraph-only posts should be detected as spam.
	 */
	public function test_only_paragraphs_are_spam() {
		wp_set_current_user( self::$user );
		$response = $this->save_block_pattern( array(
			'title'   => 'Spam Check',
			'content' => "<!-- wp:paragraph -->\n<p>Paragraph one.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Paragraph two.</p>\n<!-- /wp:paragraph -->",
			'status'  => 'publish',
		) );

		$this->assertFalse( $response->is_error() );
		$data = $response->get_data();

		$this->assertSame( SPAM_STATUS, $data['status'] );
	}
}

