<?php
/**
 * Tests for legacy pattern content decoding.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

namespace WordPressdotorg\Pattern_Directory\Tests;

use WP_HTML_Tag_Processor;
use WP_Query;
use WP_UnitTestCase;
use function WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content;
use const WordPressdotorg\Pattern_Directory\Pattern_Post_Type\POST_TYPE;

/**
 * Check legacy URL repair and block reference removal.
 */
class Pattern_Content_Decoding_Test extends WP_UnitTestCase {

	/**
	 * Loop decoding must affect patterns only, leaving other post types untouched.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_post_loop_only_decodes_patterns(): void {
		$content = '<!-- wp:navigation {"ref":34} /-->';

		foreach ( array( 'post', 'page', POST_TYPE ) as $post_type ) {
			$post               = self::factory()->post->create_and_get( array( 'post_type' => $post_type ) );
			$post->post_content = $content;

			do_action( 'the_post', $post, new WP_Query() );

			$this->assertSame(
				POST_TYPE === $post_type ? '<!-- wp:navigation {} /-->' : $content,
				$post->post_content
			);
		}
	}

	/**
	 * Preserve legacy ampersand repair even when an entity suffix follows.
	 *
	 * @dataProvider data_entity_suffixes
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 *
	 * @param string $suffix Text following the legacy ampersand marker.
	 */
	public function test_entity_suffixes_preserve_legacy_repair( string $suffix ): void {
		foreach ( array( 'u0026amp;', '\u0026amp;' ) as $marker ) {
			$content  = '<!-- wp:paragraph --><p><a href="/preview/' . $marker . $suffix . '">Preview</a></p><!-- /wp:paragraph -->';
			$expected = '<!-- wp:paragraph --><p><a href="/preview/&' . $suffix . '">Preview</a></p><!-- /wp:paragraph -->';
			$this->assertSame( $expected, decode_pattern_content( $content ) );
			$this->assertSame( $expected, decode_pattern_content( $expected ) );

			$content = '<!-- wp:navigation-link {"label":"Preview","url":"/preview/' . $marker . $suffix . '"} /-->';
			$blocks  = parse_blocks( decode_pattern_content( $content ) );
			$this->assertSame( '/preview/&' . $suffix, $blocks[0]['attrs']['url'] );
		}
	}

	/**
	 * Numeric and named entity suffixes.
	 *
	 * @return array[]
	 */
	public function data_entity_suffixes(): array {
		return array(
			'decimal'            => array( '#65;' ),
			'hexadecimal'        => array( '#x41;' ),
			'without semicolon'  => array( '#65' ),
			'named punctuation'  => array( 'colon;' ),
			'named whitespace'   => array( 'Tab;' ),
			'named line break'   => array( 'NewLine;' ),
			'named text'         => array( 'copy;' ),
			'extra amp encoding' => array( 'amp;' ),
			'legacy separator'   => array( 'b;c=2' ),
		);
	}

	/**
	 * Image query separators still repair in both serialized attributes and HTML.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_image_query_separators_are_repaired(): void {
		$content  = '<!-- wp:image {"url":"https://example.org/image?w=1200\\u0026amp;h=800"} -->';
		$content .= '<figure><img src="https://example.org/image?w=1200u0026amp;h=800" /></figure><!-- /wp:image -->';
		$decoded  = decode_pattern_content( $content );
		$blocks   = parse_blocks( $decoded );

		$this->assertSame( 'https://example.org/image?w=1200&h=800', $blocks[0]['attrs']['url'] );
		$tags = new WP_HTML_Tag_Processor( $decoded );
		$this->assertTrue( $tags->next_tag( 'IMG' ) );
		$this->assertSame( 'https://example.org/image?w=1200&h=800', $tags->get_attribute( 'src' ) );
		$this->assertSame( $decoded, decode_pattern_content( $decoded ) );
	}

	/**
	 * Extra amp encoding must still produce working HTML query separators.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_extra_amp_encoding_repairs_html_urls(): void {
		foreach ( array( 'u0026amp;', '\u0026amp;' ) as $marker ) {
			$content = '<a href="https://example.org/?w=1' . $marker . 'amp;h=2">Link</a>';
			$decoded = decode_pattern_content( $content );
			$tags    = new WP_HTML_Tag_Processor( $decoded );

			$this->assertTrue( $tags->next_tag( 'A' ) );
			$this->assertSame( 'https://example.org/?w=1&h=2', $tags->get_attribute( 'href' ) );
			$this->assertSame( $decoded, decode_pattern_content( $decoded ) );
		}
	}

	/**
	 * Content carrying no reference must pass through untouched.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_content_without_references_is_preserved(): void {
		$content = '<!-- wp:paragraph --><p>Shapes &amp; colors.</p><!-- /wp:paragraph -->';
		$this->assertSame( $content, decode_pattern_content( $content ) );

		$attributes = serialize_block_attributes(
			array(
				'url'   => 'https://example.org/image?w=1200&h=800',
				'label' => '<Shapes> -- "colors"',
			)
		);
		$content    = '<!-- wp:image ' . $attributes . ' /-->';
		$this->assertSame( $content, decode_pattern_content( $content ) );
	}

	/**
	 * Malformed legacy attributes must be preserved, with or without a reference to remove.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_malformed_attributes_are_preserved(): void {
		$content = '<!-- wp:paragraph {"placeholder":} --><p>Keep me.</p><!-- /wp:paragraph -->';
		$this->assertSame( $content, decode_pattern_content( $content ) );

		$content .= '<!-- wp:navigation {"ref":34} /-->';
		$expected = '<!-- wp:paragraph {"placeholder":} --><p>Keep me.</p><!-- /wp:paragraph --><!-- wp:navigation {} /-->';

		$this->assertSame( $expected, decode_pattern_content( $content ) );
		$this->assertSame( $expected, decode_pattern_content( $expected ) );
	}

	/**
	 * Falsy inner content must survive alongside a block that carries a reference.
	 *
	 * Guards against reserializing, whose serializer emits an empty block for a falsy body.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_falsy_block_content_survives_reference_removal(): void {
		$content  = '<!-- wp:html -->0<!-- /wp:html -->';
		$content .= '<!-- wp:navigation {"ref":34} /-->';
		$expected = '<!-- wp:html -->0<!-- /wp:html --><!-- wp:navigation {} /-->';

		$this->assertSame( $expected, decode_pattern_content( $content ) );
		$this->assertSame( $expected, decode_pattern_content( $expected ) );
	}

	/**
	 * Unbalanced delimiters must be left as they are.
	 *
	 * Guards against reserializing, which closes the group and reparents everything after it.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_unbalanced_delimiters_are_not_restructured(): void {
		$content  = '<!-- wp:group --><div>hello</div>';
		$content .= '<!-- wp:navigation {"ref":34} /-->';
		$expected = '<!-- wp:group --><div>hello</div><!-- wp:navigation {} /-->';

		$this->assertSame( $expected, decode_pattern_content( $content ) );
		$this->assertSame( $expected, decode_pattern_content( $expected ) );
	}

	/**
	 * A reference must be removed without disturbing the block's other attributes.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_sibling_attributes_survive_reference_removal(): void {
		$content  = '<!-- wp:image {"ref":34,"url":"https://example.org/i?w=1&amp;h=2"} /-->';
		$content .= '<!-- wp:paragraph --><p>Shapes &amp; colors.</p><!-- /wp:paragraph -->';
		$decoded  = decode_pattern_content( $content );
		$blocks   = parse_blocks( $decoded );

		$this->assertSame( 'https://example.org/i?w=1&amp;h=2', $blocks[0]['attrs']['url'] );
		$this->assertSame( '<p>Shapes &amp; colors.</p>', $blocks[1]['innerHTML'] );
		$this->assertSame( $decoded, decode_pattern_content( $decoded ) );
	}

	/**
	 * Attributes on blocks without a reference must be left byte for byte.
	 *
	 * Guards against reserializing, which re-encodes every block, not just the changed one.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_unreferenced_block_attributes_are_left_alone(): void {
		$content  = '<!-- wp:navigation {"ref":34} /-->';
		$content .= '<!-- wp:image {"url":"https://example.org/i?w=1&amp;h=2"} /-->';
		$expected = '<!-- wp:navigation {} /--><!-- wp:image {"url":"https://example.org/i?w=1&amp;h=2"} /-->';

		$this->assertSame( $expected, decode_pattern_content( $content ) );
		$this->assertSame( $expected, decode_pattern_content( $expected ) );
	}

	/**
	 * Remove nested block references without damaging neighboring attributes or content.
	 *
	 * @covers \WordPressdotorg\Pattern_Directory\Pattern_Post_Type\decode_pattern_content
	 */
	public function test_nested_references_are_removed(): void {
		$content  = '<!-- wp:group {"ref":12,"className":"hero"} --><div class="hero">';
		$content .= '<!-- wp:navigation {"overlayMenu":"never","ref":34} /-->';
		$content .= '<!-- wp:paragraph --><p>Keep me.</p><!-- /wp:paragraph -->';
		$content .= '</div><!-- /wp:group -->';
		$decoded  = decode_pattern_content( $content );
		$blocks   = parse_blocks( $decoded );

		$this->assertSame( array( 'className' => 'hero' ), $blocks[0]['attrs'] );
		$this->assertSame( array( 'overlayMenu' => 'never' ), $blocks[0]['innerBlocks'][0]['attrs'] );
		$this->assertSame( '<p>Keep me.</p>', $blocks[0]['innerBlocks'][1]['innerHTML'] );
		$this->assertSame( $decoded, decode_pattern_content( $decoded ) );
	}
}
