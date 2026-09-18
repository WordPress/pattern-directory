<?php
/**
 * Tests for the render-side bracket escape on a pattern's content.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_Query;
use WP_UnitTestCase;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * `validate_content()` reads stored bytes; `do_shortcode()` reads the output of block rendering. These cover
 * the gap between them, using rows written straight to the database the way a legacy pattern, a WP-CLI
 * import or a submission accepted before the rule existed would be.
 */
class Pattern_Shortcode_Rendering_Test extends WP_UnitTestCase {

	/**
	 * Render a post's content the way `core/post-content` does, with the post as the global.
	 *
	 * @param int $post_id The post to render.
	 *
	 * @return string The filtered content.
	 */
	private function render( int $post_id ): string {
		$query = new WP_Query(
			array(
				'p'           => $post_id,
				'post_type'   => 'any',
				'post_status' => 'any',
			)
		);
		$query->the_post();

		$rendered = apply_filters( 'the_content', get_the_content() );
		wp_reset_postdata();

		return (string) $rendered;
	}

	/**
	 * A shortcode in a pattern's content is text by the time a callback could run.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\escape_shortcode_syntax_in_pattern
	 */
	public function test_shortcode_in_content_does_not_run(): void {
		$pattern = self::factory()->post->create(
			array(
				'post_type'    => POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => "<!-- wp:paragraph -->\n<p>PROBE [caption id=c width=1 caption=hello]body[/caption]</p>\n<!-- /wp:paragraph -->",
			)
		);

		$rendered = $this->render( $pattern );

		$this->assertStringNotContainsString( 'wp-caption', $rendered );
		$this->assertStringContainsString( '&#91;caption', $rendered );
	}

	/**
	 * A bracket that only exists once the block is parsed is escaped too, because this runs after `do_blocks`.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\escape_shortcode_syntax_in_pattern
	 */
	public function test_shortcode_from_a_block_attribute_does_not_run(): void {
		self::factory()->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'Probe Cat',
			)
		);

		$label   = '[caption id=c width=1 caption=x\x3cspan\x20\x64ata-wp-interactive\x3d\x22probe\x22\x3ehi\x3c/span\x3e]body[/caption]';
		$json    = str_replace(
			array( '[', ']' ),
			array( '[', ']' ),
			(string) wp_json_encode(
				array(
					'displayAsDropdown' => true,
					'showLabel'         => true,
					'label'             => $label,
				)
			)
		);
		$pattern = self::factory()->post->create(
			array(
				'post_type'    => POST_TYPE,
				'post_status'  => 'publish',
				'post_content' => wp_slash( "<!-- wp:categories $json /-->" ),
			)
		);

		$rendered = $this->render( $pattern );

		$this->assertStringNotContainsString( 'wp-caption', $rendered );
		$this->assertStringNotContainsString( 'data-wp-', $rendered );
	}

	/**
	 * The escape is scoped to patterns, so an ordinary post still expands its shortcodes.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\escape_shortcode_syntax_in_pattern
	 */
	public function test_other_post_types_still_expand_shortcodes(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '<p>[caption id=c width=1 caption=hello]body[/caption]</p>',
			)
		);

		$rendered = $this->render( $post_id );

		$this->assertStringContainsString( 'wp-caption', $rendered );
	}
}
