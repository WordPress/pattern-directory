<?php

namespace WordPressdotorg\Pattern_Translations;

require_once __DIR__ . '/BlockParser.php';

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
class TextNodeParser implements BlockParser {
	use DomUtils;

	public function to_strings( array $block ) : array {
		$dom   = $this->get_dom( serialize_block( $block ) );
		$xpath = new \DOMXPath( $dom );

		$strings = [];

		foreach ( $xpath->query( '//text()' ) as $text ) {
			if ( trim( $text->nodeValue ) ) {
				$strings[] = $text->nodeValue;
			}
		}

		return $strings;
	}

	public function replace_strings( array $block, array $replacements ) : array {
		$dom   = $this->get_dom( serialize_block( $block ) );
		$xpath = new \DOMXPath( $dom );

		foreach ( $xpath->query( '//text()' ) as $text ) {
			if ( trim( $text->nodeValue ) && isset( $replacements[ $text->nodeValue ] ) ) {
				$text->parentNode->replaceChild( $dom->createCDATASection( $replacements[ $text->nodeValue ] ), $text );
			}
		}

		return parse_blocks( $this->removeHtml( $dom->saveHTML() ) )[0] ?? [];
	}
}
// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
