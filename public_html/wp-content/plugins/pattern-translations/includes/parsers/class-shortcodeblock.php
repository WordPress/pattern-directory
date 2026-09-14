<?php
/**
 * Block translation parser helpers.
 *
 * @package WordPressdotorg\Pattern_Translations
 */

namespace WordPressdotorg\Pattern_Translations\Parsers;

/**
 * Handle blocks with attributes present in the attributes that are also used
 * in a shortcode within the block content.
 *
 * Example: new Parsers\ShortcodeBlock( [ 'subscribePlaceholder', 'submitButtonText' ] );
 */
class ShortcodeBlock implements BlockParser {
	use GetSetAttribute;

	/**
	 * Translatable shortcode attributes.
	 *
	 * @var string[]
	 */
	public $attribute_names = array();

	/**
	 * Set the translatable shortcode attributes.
	 *
	 * @param array $attribute_names Attribute names.
	 */
	public function __construct( array $attribute_names ) {
		$this->attribute_names = $attribute_names;
	}

	/**
	 * Extract translatable block strings.
	 *
	 * @param array $block Parsed block.
	 * @return array Extracted strings.
	 */
	public function to_strings( array $block ): array {
		$strings = array();
		foreach ( $this->attribute_names as $attribute_name ) {
			$strings = array_merge( $strings, $this->get_attribute( $attribute_name, $block ) );
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
		foreach ( $this->attribute_names as $attribute_name ) {
			$this->set_attribute( $attribute_name, $block, $replacements );

			foreach ( $block['innerContent'] as $i => &$inner_content ) {
				if ( is_string( $inner_content ) ) {
					$shortcode_param_regex = '/(\b' . $this->snake_case( $attribute_name ) . ')="(.*?)("\n?)/';

					$block['innerContent'][ $i ] = preg_replace_callback(
						$shortcode_param_regex,
						function ( $matches ) use ( $replacements ) {
							return $this->preg_replace_gutenberg_attributes_handler( $matches, $replacements );
						},
						$inner_content
					);
				}
			}
		}

		$regex              = '/\b(\w*?)="(.*?)(")/';
		$block['innerHTML'] = preg_replace_callback(
			$regex,
			function ( $matches ) use ( $replacements ) {
				return $this->preg_replace_gutenberg_attributes_handler( $matches, $replacements );
			},
			$block['innerHTML']
		);
		return $block;
	}

	/**
	 * Convert an attribute name to shortcode casing.
	 *
	 * @param string $camel_case_string Attribute name.
	 * @return string Shortcode attribute name.
	 */
	protected function snake_case( $camel_case_string ) {
		return ltrim(
			preg_replace_callback(
				'/([A-Z]+)/',
				function ( $matches ) {
					return '_' . strtolower( $matches[1] ); },
				$camel_case_string
			),
			'_'
		);
	}

	/**
	 * Replace the value of a matched shortcode attribute.
	 *
	 * @param array $matches      Regular expression matches.
	 * @param array $replacements Translations keyed by original string.
	 * @return string Updated attribute.
	 */
	public function preg_replace_gutenberg_attributes_handler( array $matches, array $replacements ) {
		$current_value = $matches[2];

		if ( ! isset( $replacements[ $current_value ] ) ) {
			return $matches[0];
		}

		$new_value = $replacements[ $current_value ];
		$property  = $matches[1];
		return "$property=\"$new_value" . $matches[3];
	}
}
