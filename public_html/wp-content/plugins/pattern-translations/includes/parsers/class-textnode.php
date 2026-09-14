<?php
/**
 * Block translation parser helpers.
 *
 * @package WordPressdotorg\Pattern_Translations
 */

namespace WordPressdotorg\Pattern_Translations\Parsers;

/**
 * Translate TextNode block content.
 */
class TextNode implements BlockParser {
	use DomUtils;

	/**
	 * Extract translatable block strings.
	 *
	 * @param array $block Parsed block.
	 * @return array Extracted strings.
	 */
	public function to_strings( array $block ): array {
		$dom   = $this->get_dom( serialize_block( $block ) );
		$xpath = new \DOMXPath( $dom );

		$strings = array();

		foreach ( $xpath->query( '//text()' ) as $text ) {
			if ( trim( $text->nodeValue ) ) {
				$strings[] = $text->nodeValue;
			}
		}

		return $strings;
	}

	/**
	 * Replace translated strings in a block.
	 *
	 * @param array $block        Parsed block.
	 * @param array $replacements Translations keyed by original string.
	 * @return array Updated block.
	 */
	public function replace_strings( array $block, array $replacements ): array {
		$dom   = $this->get_dom( serialize_block( $block ) );
		$xpath = new \DOMXPath( $dom );

		foreach ( $xpath->query( '//text()' ) as $text ) {
			if ( trim( $text->nodeValue ) && isset( $replacements[ $text->nodeValue ] ) ) {
				$text->parentNode->replaceChild( $dom->createCDATASection( $replacements[ $text->nodeValue ] ), $text );
			}
		}

		return parse_blocks( $this->removeHtml( $dom->saveHTML() ) )[0] ?? array();
	}
}
