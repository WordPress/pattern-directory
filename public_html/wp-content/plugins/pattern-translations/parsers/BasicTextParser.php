<?php

namespace WordPressdotorg\Pattern_Translations;

require_once __DIR__ . '/BlockParser.php';

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
class BasicTextParser implements BlockParser {
	use DomUtils;

	public function to_strings( array $block ) : array {
		$dom   = $this->get_dom( $block['innerHTML'] );
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
		$dom   = $this->get_dom( $block['innerHTML'] );
		$xpath = new \DOMXPath( $dom );

		foreach ( $xpath->query( '//text()' ) as $text ) {
			if ( trim( $text->nodeValue ) && isset( $replacements[ $text->nodeValue ] ) ) {
				$text->parentNode->replaceChild( $dom->createCDATASection( $replacements[ $text->nodeValue ] ), $text );
			}
		}

		$block['innerHTML'] = trim( $this->removeHtml( $dom->saveHTML() ) );

		foreach ( $block['innerContent'] as &$inner_content ) {
			if ( is_string( $inner_content ) ) {
				$dom = $this->get_dom( $inner_content );
				$xpath = new \DOMXPath( $dom );

				$text_nodes = $xpath->query( '//text()' );

				// Only update text matches that are found outside of HTML tags.
				// This approach does not use $dom->saveHTML because innerContent includes
				// unclosed HTML tags, and saveHTML adds extra closed tags.
				foreach ( $text_nodes as $text ) {
					if ( trim( $text->nodeValue ) && isset( $replacements[ $text->nodeValue ] ) ) {
						$regex = '#(<([^>]*)>)?' . preg_quote( $text->nodeValue, '/' ) . '(<([^>]*)>)?#is';
						$inner_content = preg_replace( $regex, '${1}' . $replacements[ $text->nodeValue ] . '${3}', $inner_content );
					}
				}
			}
		}

		return $block;
	}
}
// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
