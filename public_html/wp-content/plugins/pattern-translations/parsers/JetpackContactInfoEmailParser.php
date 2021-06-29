<?php

namespace A8C\Lib\Patterns;

require_once __DIR__ . '/BlockParser.php';

class JetpackContactInfoEmailParser implements BlockParser {
	use GetSetAttribute;
	use DomUtils;

	public function to_strings( array $block ) : array {
		return $this->get_attribute( 'email', $block );
	}

	public function replace_strings( array $block, array $replacements ) : array {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$strings = $this->to_strings( $block );

		if ( ! empty( $strings ) && isset( $replacements[ $strings[0] ] ) ) {
			$original_email = $strings[0];
			$new_email      = $replacements[ $original_email ];
			$this->set_attribute( 'email', $block, $replacements );

			$dom   = $this->get_dom( $block['innerHTML'] );
			$xpath = new \DOMXPath( $dom );

			foreach ( $xpath->query( "//*[contains(@class, 'wp-block-jetpack-email')]" ) as $email_container ) {
				foreach ( $email_container->childNodes as $child ) {
					if ( XML_ELEMENT_NODE != $child->nodeType ) {
						continue;
					}
					if ( 'a' === $child->tagName ) {
						$child->setAttribute( 'href', 'mailto:' . $new_email );
						$child->nodeValue = $new_email;
					}
				}
			}

			$updated_html = trim( $this->removeHtml( $dom->saveHTML() ) );

			$block['innerHTML']    = $updated_html;
			$block['innerContent'] = [ $updated_html ];
		}
		return $block;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
