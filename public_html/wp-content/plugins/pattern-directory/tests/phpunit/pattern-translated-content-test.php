<?php
/**
 * Test the guard on assembled translated pattern content.
 *
 * @package WordPress\Pattern_Directory
 */

declare( strict_types = 1 );

use function WordPressdotorg\Pattern_Translations\is_translated_content_allowed;

/**
 * Test translated content validation.
 *
 * @group content-validation
 */
class Pattern_Translated_Content_Test extends WP_UnitTestCase {
	/**
	 * An Interactivity directive is refused wherever a translated string can carry it.
	 *
	 * Translator strings are written into block attributes as well as inner HTML, and the REST validators
	 * never see this content, so both channels have to be checked here.
	 *
	 * @dataProvider data_disallowed_content
	 *
	 * @param string $html The assembled translated markup.
	 */
	public function test_directives_are_refused( string $html ): void {
		$this->assertFalse( is_translated_content_allowed( $html ) );
	}

	/**
	 * Ordinary translated content still passes.
	 *
	 * @dataProvider data_allowed_content
	 *
	 * @param string $html The assembled translated markup.
	 */
	public function test_ordinary_content_is_allowed( string $html ): void {
		$this->assertTrue( is_translated_content_allowed( $html ) );
	}

	/**
	 * Markup a translated pattern must never be stored with.
	 *
	 * @return array[]
	 */
	public function data_disallowed_content(): array {
		return array(
			'directive in inner HTML'      => array( "<!-- wp:paragraph -->\n<p><span data-wp-interactive=\"x\" data-wp-init=\"actions.go\">t</span></p>\n<!-- /wp:paragraph -->" ),
			'directive in a raw-text el'   => array( "<!-- wp:paragraph -->\n<p><style><span data-wp-interactive=\"x\" data-wp-init=\"actions.go\">t</span></style></p>\n<!-- /wp:paragraph -->" ),
			// `<svg>` opens foreign content, so the tokenizer reads the nested `<script>` as text while KSES keeps what it held.
			'directive in foreign content' => array( "<!-- wp:html -->\n<svg><script><a href=\"#\" data-wp-interactive=\"x\" data-wp-bind--href=\"context.url\">t</a></svg>\n<!-- /wp:html -->" ),
			'directive in an attribute'    => array( '<!-- wp:heading {"placeholder":"<span data-wp-interactive="x" data-wp-init="actions.go">t</span>"} -->' . "\n<h2>t</h2>\n<!-- /wp:heading -->" ),
			'disallowed block'             => array( '<!-- wp:shortcode -->[gallery]<!-- /wp:shortcode -->' ),
		);
	}

	/**
	 * Markup a translated pattern is expected to carry.
	 *
	 * @return array[]
	 */
	public function data_allowed_content(): array {
		return array(
			'plain translation'      => array( "<!-- wp:paragraph -->\n<p>Bonjour tout le monde.</p>\n<!-- /wp:paragraph -->" ),
			'translated placeholder' => array( '<!-- wp:heading {"placeholder":"Titre"} -->' . "\n<h2>Titre</h2>\n<!-- /wp:heading -->" ),
		);
	}
}
