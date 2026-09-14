<?php
/**
 * Block translation parser helpers.
 *
 * @package WordPressdotorg\Pattern_Translations
 */

namespace WordPressdotorg\Pattern_Translations\Parsers;

/**
 * Translate BasicText block content.
 */
class BasicText implements BlockParser {
	use DomUtils;
	use TextNodesXPath;

	/**
	 * Extract translatable block strings.
	 *
	 * @param array $block Parsed block.
	 * @return array Extracted strings.
	 */
	public function to_strings( array $block ): array {
		$dom   = $this->get_dom( $block['innerHTML'] );
		$xpath = new \DOMXPath( $dom );

		$strings = array();

		foreach ( $xpath->query( $this->text_nodes_xpath_query() ) as $text ) {
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
		$dom         = $this->get_dom( $block['innerHTML'] );
		$xpath       = new \DOMXPath( $dom );
		$xpath_query = $this->text_nodes_xpath_query();

		foreach ( $xpath->query( $xpath_query ) as $text ) {
			if ( trim( $text->nodeValue ) && isset( $replacements[ $text->nodeValue ] ) ) {
				$text->parentNode->replaceChild( $dom->createCDATASection( $replacements[ $text->nodeValue ] ), $text );
			}
		}

		$block['innerHTML'] = $this->remove_html( $dom->saveHTML() );

		foreach ( $block['innerContent'] as &$inner_content ) {
			if ( is_string( $inner_content ) ) {
				$dom   = $this->get_dom( $inner_content );
				$xpath = new \DOMXPath( $dom );

				$text_nodes = $xpath->query( $xpath_query );

				/*
				 * Only update text matches that are found outside of HTML tags.
				 * This approach does not use $dom->saveHTML because innerContent includes
				 * unclosed HTML tags, and saveHTML adds extra closed tags.
				 */
				foreach ( $text_nodes as $text ) {
					if ( trim( $text->nodeValue ) && isset( $replacements[ $text->nodeValue ] ) ) {
						$regex         = '#(<([^>]*)>)?' . preg_quote( $text->nodeValue, '/' ) . '(<([^>]*)>)?#is';
						$inner_content = preg_replace( $regex, '${1}' . addcslashes( $replacements[ $text->nodeValue ], '\\$' ) . '${3}', $inner_content );
					}
				}
			}
		}

		return $block;
	}
}
