<?php
/**
 * Test the theme's copy button.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_HTML_Tag_Processor;
use WP_UnitTestCase;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * The copied markup must match what the API serves, without local media IDs or refs (#698).
 */
class Theme_Copy_Button_Test extends WP_UnitTestCase {

	/**
	 * Render the copy button for a post and return the markup it would copy.
	 *
	 * @param int $post_id The pattern ID.
	 *
	 * @return string
	 */
	private function get_copied_content( int $post_id ): string {
		// The variables `render.php` reads, as `register_block_type()` passes them.
		$attributes = array();
		$block      = (object) array( 'context' => array( 'postId' => $post_id ) );

		ob_start();
		require dirname( __DIR__, 4 ) . '/themes/wporg-pattern-directory-2024/src/blocks/copy-button/render.php';
		$html = ob_get_clean();

		$tags = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $tags->next_tag( array( 'class_name' => 'wp-block-wporg-copy-button__content' ) ) );

		return json_decode( rawurldecode( (string) $tags->get_attribute( 'value' ) ) );
	}

	/**
	 * Catches copied markup that keeps an image ID from the site the pattern was built on.
	 */
	public function test_copied_content_drops_media_ids_and_refs(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => POST_TYPE,
				'post_content' => '<!-- wp:image {"id":334,"sizeSlug":"large"} --><figure class="wp-block-image size-large"><img src="https://example.com/a.jpg" alt="" class="wp-image-334"/></figure><!-- /wp:image --><!-- wp:block {"ref":12} /-->',
			)
		);

		$copied = $this->get_copied_content( $post_id );

		$blocks = parse_blocks( $copied );
		$this->assertSame( array( 'sizeSlug' => 'large' ), $blocks[0]['attrs'] );
		$this->assertSame( array(), $blocks[1]['attrs'] );

		// How kses serializes the tag varies between core versions, so check the attribute, not the markup.
		$img = new WP_HTML_Tag_Processor( $copied );
		$this->assertTrue( $img->next_tag( 'img' ) );
		$this->assertNull( $img->get_attribute( 'class' ) );
		$this->assertSame( 'https://example.com/a.jpg', $img->get_attribute( 'src' ) );
	}
}
