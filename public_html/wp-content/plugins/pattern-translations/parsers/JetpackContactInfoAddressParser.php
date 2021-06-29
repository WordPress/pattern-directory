<?php

namespace A8C\Lib\Patterns;

require_once __DIR__ . '/BlockParser.php';

class JetpackContactInfoAddressParser implements BlockParser {
	use GetSetAttribute;
	use DomUtils;

	private $address_attributes = [
		'address'      => 'jetpack-address__address1',
		'addressLine2' => 'jetpack-address__address2',
		'addressLine3' => 'jetpack-address__address3',
		'city'         => 'jetpack-address__city',
		'region'       => 'jetpack-address__region',
		'postal'       => 'jetpack-address__postal',
		'country'      => 'jetpack-address__country',
	];

	public function to_strings( array $block ) : array {
		$strings = [];
		foreach ( $this->address_attributes as $key => $_attribute ) {
			$strings = array_merge( $strings, $this->get_attribute( $key, $block ) );
		}
		return $strings;
	}

	public function replace_strings( array $block, array $replacements ) : array {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$dom   = $this->get_dom( $block['innerHTML'] );
		$xpath = new \DOMXPath( $dom );

		foreach ( $this->address_attributes as $address_attr => $address_class ) {
			foreach ( $xpath->query( "//*[contains(@class, '$address_class')]" ) as $address_node ) {
				$block_attribute_value = $this->get_attribute( $address_attr, $block );
				if ( ! empty( $block_attribute_value ) && isset( $replacements[ $block_attribute_value[0] ] ) ) {
					$address_node->nodeValue = $replacements[ $block_attribute_value[0] ];
				}
			}
		}

		foreach ( $this->address_attributes as $address_attr => $address_class ) {
			$this->set_attribute( $address_attr, $block, $replacements );
		}

		$updated_html = trim( $this->removeHtml( $dom->saveHTML() ) );

		$block['innerHTML']    = $updated_html;
		$block['innerContent'] = [ $updated_html ];

		return $block;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
