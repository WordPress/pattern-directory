<?php

namespace A8C\Lib\Patterns;

require_once __DIR__ . '/BlockParser.php';

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
class JetpackContactInfoPhoneParser implements BlockParser {
	use GetSetAttribute;
	use DomUtils;

	public function to_strings( array $block ) : array {
		return $this->get_attribute( 'phone', $block );
	}

	public function replace_strings( array $block, array $replacements ) : array {
		// Logic based on https://github.com/Automattic/jetpack/blob/HEAD/extensions/blocks/contact-info/phone/save.js#L5
		$strings = $this->to_strings( $block );

		if ( ! empty( $strings ) && isset( $replacements[ $strings[0] ] ) ) {
			$original_phone = $strings[0];
			$new_phone      = $replacements[ $original_phone ];

			$this->set_attribute( 'phone', $block, $replacements );

			$dom   = $this->get_dom( $block['innerHTML'] );
			$xpath = new \DOMXPath( $dom );

			$matches = [];
			preg_match( '/\d+\.\d+|\d+\b|\d+(?=\w)/', $new_phone, $matches );

			if ( empty( $matches ) ) {
				foreach ( $xpath->query( "//*[contains(@class, 'wp-block-jetpack-phone')]" ) as $phone_container ) {
					$phone_container->nodeValue = $new_phone;
				}
			} else {
				$index_of_first_number = mb_strpos( $new_phone, $matches[0] );

				// Assume that eveything after the first number should be part of the phone number.
				// care about the first prefix character.
				$phone_number = $index_of_first_number ? mb_substr( $new_phone, $index_of_first_number - 1 ) : $new_phone;
				$prefix       = $index_of_first_number ? mb_substr( $new_phone, 0, $index_of_first_number ) : '';
				$just_number  = preg_replace( '/\D/', '', $phone_number );

				// Phone numbers starting with + should be part of the number.
				if ( preg_match( '#[0-9/+/(]#', $phone_number[0] ) ) {
					// Remove the special character from the end of the prefix so they don't appear twice.
					$prefix = mb_substr( $prefix, 0, -1 );
					// Phone numbers starting with + shoud be part of the number.
					if ( '+' === $phone_number[0] ) {
						$just_number = '+' . $just_number;
					}
				} else {
					// Remove the first character
					$phone_number = mb_substr( $phone_number, 1 );
				}
				foreach ( $xpath->query( "//*[contains(@class, 'wp-block-jetpack-phone')]" ) as $phone_container ) {
					foreach ( $phone_container->childNodes as $child ) {
						if ( XML_ELEMENT_NODE != $child->nodeType ) {
							continue;
						}
						if ( 'span' === $child->tagName && 'phone-prefix' === $child->getAttribute( 'class' ) ) {
							$child->nodeValue = $prefix;
						}
						if ( 'a' === $child->tagName ) {
							$child->setAttribute( 'href', 'tel:' . $just_number );
							$child->nodeValue = $phone_number;
						}
					}
				}
			}

			$updated_html = trim( $this->removeHtml( $dom->saveHTML() ) );

			$block['innerHTML']    = $updated_html;
			$block['innerContent'] = [ $updated_html ];
		}

		return $block;
	}
}
// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
